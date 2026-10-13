<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Layerok\PosterPos\Models\DeliveryEvent;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierExcludedReceipt;
use Layerok\PosterPos\Models\CourierTask;

/**
 * What couriers delivered: closed Poster receipts and our own tasks (Довоз,
 * Закупка, Магазин) in one list. The payment page reads a day of it; the courier
 * app's history will read a range for one courier.
 *
 * Both kinds come out as the same item shape:
 *   kind      'receipt' | 'task'
 *   id        receipt number or task id
 *   type      'Заказ' or the task type label
 *   at        local 'Y-m-d H:i:s', for sorting
 *   date, time
 *   title     address for a receipt, details for a task
 *   note      client for a receipt, comment for a task
 */
class CourierLedger
{
    private OpenDeliveries $deliveries;

    public function __construct(?OpenDeliveries $deliveries = null)
    {
        $this->deliveries = $deliveries ?: new OpenDeliveries();
    }

    /**
     * Everything delivered on one local day, grouped by courier.
     *
     * @return array{
     *     couriers: array<int, array{receipts: array, tasks: array}>,
     *     unmapped: array<int, array>,
     *     no_courier: int,
     *     open: array<int, int>,
     * }
     *   couriers   keyed by our courier id
     *   unmapped   receipts whose Poster employee has no courier record, keyed by Poster user id
     *   no_courier closed receipts nobody was assigned to
     *   open       receipts still open with a courier on them, counted per our courier id
     */
    public function forDate(string $date): array
    {
        $closed = $this->deliveries->closedTransactions($date, $date);
        $open = $this->deliveries->openTransactions($date, $date);

        $couriers = Courier::whereNotNull('poster_user_id')->get()->keyBy(fn($c) => (int) $c->poster_user_id);
        $excluded = $this->excludedIds($closed->pluck('transaction_id'));

        $result = ['couriers' => [], 'unmapped' => [], 'no_courier' => 0, 'open' => []];

        foreach ($closed as $transaction) {
            $posterUserId = OpenDeliveries::courierIdOf($transaction);

            if (!$posterUserId) {
                $result['no_courier']++;
                continue;
            }

            $item = $this->receiptItem($transaction, $excluded);
            $courier = $couriers->get($posterUserId);

            if ($courier) {
                $result['couriers'][$courier->id]['receipts'][] = $item;
            } else {
                $result['unmapped'][$posterUserId][] = $item;
            }
        }

        foreach ($open as $transaction) {
            $courier = $couriers->get((int) OpenDeliveries::courierIdOf($transaction));

            if ($courier) {
                $result['open'][$courier->id] = ($result['open'][$courier->id] ?? 0) + 1;
            }
        }

        foreach (CourierTask::onLocalDate($date)->get() as $task) {
            $result['couriers'][$task->courier_id]['tasks'][] = $this->taskItem($task);
        }

        foreach ($result['couriers'] as &$entry) {
            $entry += ['receipts' => [], 'tasks' => []];
        }

        return $result;
    }

    /**
     * One courier's history over a date range, newest first. Excluded receipts
     * are kept but flagged, so the courier can see what was not paid.
     */
    public function history(Courier $courier, string $from, string $to): Collection
    {
        $receipts = collect();

        if ($courier->poster_user_id) {
            $mine = fn($t) => OpenDeliveries::courierIdOf($t) === (int) $courier->poster_user_id;

            $closed = $this->deliveries->closedTransactions($from, $to)->filter($mine);
            $excluded = $this->excludedIds($closed->pluck('transaction_id'));

            $receipts = $closed->map(fn($t) => $this->receiptItem($t, $excluded) + $this->appDetails($t) + [
                'closed' => true,
                'can_return' => false,
            ]);

            $receipts = $receipts->merge($this->deliveredStillOpen($mine, $from, $to));
        }

        $tasks = CourierTask::where('courier_id', $courier->id)
            ->betweenLocalDates($from, $to)
            ->get()
            ->map(fn(CourierTask $task) => $this->taskItem($task));

        return $receipts->merge($tasks)->sortByDesc('at')->values();
    }

    public static function sort(array $items): array
    {
        usort($items, fn($a, $b) => strcmp($b['at'], $a['at']));

        return $items;
    }

    /**
     * Orders the courier marked delivered (processing_status 50) that the POS
     * hasn't closed yet. They count as delivered in the app's archive, and only
     * these can be brought back to the courier's list.
     *
     * An open receipt has no close time, so its time is the moment the webhook
     * saw it become 50; failing that, the promised delivery time.
     */
    private function deliveredStillOpen(callable $mine, string $from, string $to): Collection
    {
        // Poster files open receipts under the day they were opened, so an order
        // opened yesterday and delivered today starts a day earlier.
        $since = Carbon::parse($from, OpenDeliveries::TIMEZONE)->subDay()->toDateString();

        $delivered = $this->deliveries->openTransactions($since, $to)
            ->filter($mine)
            ->filter(fn($t) => (int) ($t->processing_status ?? 0) >= CourierOrders::DELIVERED)
            ->values();

        if ($delivered->isEmpty()) {
            return collect();
        }

        $seenAt = DeliveryEvent::whereIn('transaction_id', $delivered->pluck('transaction_id')->all())
            ->where('processing_status', '>=', CourierOrders::DELIVERED)
            ->get(['transaction_id', 'happened_at'])
            ->groupBy('transaction_id')
            ->map(fn($rows) => $rows->min('happened_at'));

        return $delivered
            ->map(function ($t) use ($seenAt) {
                $event = $seenAt->get($t->transaction_id);
                $at = $event
                    ? $event->copy()->setTimezone(OpenDeliveries::TIMEZONE)
                    : (OpenDeliveries::dueAt($t) ?? OpenDeliveries::now());

                return [
                    'kind' => 'receipt',
                    'id' => (string) $t->transaction_id,
                    'type' => 'Заказ',
                    'at' => $at->format('Y-m-d H:i:s'),
                    'date' => $at->toDateString(),
                    'time' => $at->format('H:i'),
                    'title' => OpenDeliveries::addressOf($t),
                    'note' => OpenDeliveries::clientName($t->client_firstname ?? null, $t->client_lastname ?? null),
                    'amount' => OpenDeliveries::amountOf($t),
                    'spot_poster_id' => (int) ($t->spot_id ?? 0),
                    'excluded' => false,
                    'closed' => false,
                    'can_return' => true,
                ] + $this->appDetails($t);
            })
            ->filter(fn($item) => $item['date'] >= $from && $item['date'] <= $to)
            ->values();
    }

    /**
     * What the courier app's archive shows on a receipt besides the basics.
     */
    private function appDetails(object $transaction): array
    {
        return [
            'phone' => preg_replace('/[^+0-9]/', '', (string) ($transaction->client_phone ?? '')),
            'comment' => trim((string) ($transaction->transaction_comment ?? '')),
            'payment' => CourierOrders::paymentOf($transaction),
        ];
    }

    private function receiptItem(object $transaction, array $excluded): array
    {
        $closedAt = OpenDeliveries::localTime($transaction->date_close_date ?? null);
        $id = (string) $transaction->transaction_id;

        return [
            'kind' => 'receipt',
            'id' => $id,
            'type' => 'Заказ',
            'at' => $closedAt ? $closedAt->format('Y-m-d H:i:s') : '',
            'date' => $closedAt ? $closedAt->toDateString() : '',
            'time' => $closedAt ? $closedAt->format('H:i') : '',
            'title' => OpenDeliveries::addressOf($transaction),
            'note' => OpenDeliveries::clientName($transaction->client_firstname ?? null, $transaction->client_lastname ?? null),
            'amount' => OpenDeliveries::amountOf($transaction),
            'spot_poster_id' => (int) ($transaction->spot_id ?? 0),
            'excluded' => in_array($id, $excluded, true),
        ];
    }

    private function taskItem(CourierTask $task): array
    {
        $at = $task->performed_at->copy()->setTimezone(OpenDeliveries::TIMEZONE);

        return [
            'kind' => 'task',
            'id' => (string) $task->id,
            'type' => $task->type_label,
            'type_code' => $task->type,
            'at' => $at->format('Y-m-d H:i:s'),
            'date' => $at->toDateString(),
            'time' => $at->format('H:i'),
            'title' => (string) $task->details,
            'note' => (string) $task->comment,
            'source' => $task->source,
            'spot_id' => $task->spot_id,
            'excluded' => false,
            'can_return' => false,
        ];
    }

    private function excludedIds(Collection $transactionIds): array
    {
        if ($transactionIds->isEmpty()) {
            return [];
        }

        return CourierExcludedReceipt::whereIn('transaction_id', $transactionIds->all())
            ->pluck('transaction_id')
            ->map(fn($id) => (string) $id)
            ->all();
    }
}
