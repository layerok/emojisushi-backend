<?php

namespace Layerok\PosterPos\Classes\Delivery;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Layerok\PosterPos\Models\DeliveryEvent;
use Layerok\PosterPos\Models\DeliverySnapshot;

/**
 * Turns Poster webhooks into the operator's event feed.
 *
 * Poster only reports that a receipt changed, not what changed, so each webhook
 * re-reads the receipt and compares it with the snapshot left by the previous
 * one. Repeated or retried webhooks therefore produce no duplicate events.
 */
class DeliveryEventRecorder
{
    // dash.getTransaction. 0 is an online order the register has not accepted yet
    // (it already has a receipt); the incoming_order webhook covers that stage.
    // 4 shows up on cancelled orders.
    private const STATUS_UNACCEPTED = 0;
    private const STATUS_OPEN = 1;
    private const STATUS_CLOSED = 2;
    private const STATUS_REMOVED = 3;
    private const STATUS_CANCELED = 4;

    // incomingOrders.getIncomingOrder: 0 — new, 1 — accepted, 7 — cancelled
    private const INCOMING_NEW = 0;
    private const INCOMING_ACCEPTED = 1;
    private const INCOMING_CANCELED = 7;

    /**
     * Events and snapshots older than this are pruned now and then, from the
     * webhook itself since nothing else runs in the background.
     */
    private const KEEP_DAYS = 30;

    private OpenDeliveries $deliveries;

    public function __construct(?OpenDeliveries $deliveries = null)
    {
        $this->deliveries = $deliveries ?: new OpenDeliveries();
    }

    public function recordTransaction(int $transactionId, string $action, ?int $time = null): void
    {
        $at = $this->moment($time);

        // A removed receipt can no longer be read back, so the snapshot is all
        // there is to go on.
        $transaction = $action === 'removed' ? null : $this->deliveries->transaction($transactionId);

        if (!$transaction) {
            $this->markGone($transactionId, $at);

            return;
        }

        if (!$this->isDelivery($transaction)) {
            return;
        }

        try {
            DB::transaction(fn() => $this->diff($transactionId, $transaction, $at));
        } catch (QueryException $e) {
            // Two webhooks for a brand-new receipt raced to create its snapshot;
            // the other one has already recorded the event.
            if (!$this->isDuplicateKey($e)) {
                throw $e;
            }
        }

        $this->pruneSometimes();
    }

    public function recordIncomingOrder(int $incomingOrderId, string $action, ?int $time = null): void
    {
        $at = $this->moment($time);
        $seen = DeliveryEvent::where('incoming_order_id', $incomingOrderId)->pluck('type')->all();
        $settled = in_array(DeliveryEvent::TYPE_INCOMING_ACCEPTED, $seen, true)
            || in_array(DeliveryEvent::TYPE_INCOMING_CANCELED, $seen, true);

        if ($action === 'removed') {
            $first = DeliveryEvent::where('incoming_order_id', $incomingOrderId)
                ->where('type', DeliveryEvent::TYPE_INCOMING_NEW)
                ->first();

            if ($first && !$settled) {
                $this->event(DeliveryEvent::TYPE_INCOMING_CANCELED, $at, [
                    'incoming_order_id' => $incomingOrderId,
                    'spot_poster_id' => $first->spot_poster_id,
                    'address' => $first->address,
                    'client' => $first->client,
                ]);
            }

            return;
        }

        $order = $this->deliveries->incomingOrder($incomingOrderId);

        if (!$order || (int) ($order->service_mode ?? 0) !== OpenDeliveries::SERVICE_MODE_DELIVERY) {
            return;
        }

        $fields = [
            'incoming_order_id' => $incomingOrderId,
            'transaction_id' => !empty($order->transaction_id) ? (int) $order->transaction_id : null,
            'spot_poster_id' => !empty($order->spot_id) ? (int) $order->spot_id : null,
            'address' => $this->limit($order->address ?? null),
            'client' => $this->limit(OpenDeliveries::clientName($order->first_name ?? null, $order->last_name ?? null)),
        ];

        $status = (int) ($order->status ?? self::INCOMING_NEW);

        if (!in_array(DeliveryEvent::TYPE_INCOMING_NEW, $seen, true) && !$settled) {
            $this->event(DeliveryEvent::TYPE_INCOMING_NEW, $this->moment(
                OpenDeliveries::localTime($order->created_at ?? null)?->getTimestamp() ?? $time
            ), $fields);
        }

        if ($settled) {
            return;
        }

        if ($status === self::INCOMING_ACCEPTED) {
            $this->event(DeliveryEvent::TYPE_INCOMING_ACCEPTED, $at, $fields);
        } elseif ($status === self::INCOMING_CANCELED) {
            $this->event(DeliveryEvent::TYPE_INCOMING_CANCELED, $at, $fields);
        }
    }

    private function diff(int $transactionId, object $transaction, Carbon $at): void
    {
        $snapshot = DeliverySnapshot::where('transaction_id', $transactionId)->lockForUpdate()->first();

        $status = (int) ($transaction->status ?? self::STATUS_OPEN);
        $courierId = OpenDeliveries::courierIdOf($transaction);
        $processing = isset($transaction->processing_status) ? (int) $transaction->processing_status : null;

        $fields = [
            'transaction_id' => $transactionId,
            'spot_poster_id' => !empty($transaction->spot_id) ? (int) $transaction->spot_id : null,
            'courier_id' => $courierId,
            'processing_status' => $processing,
            'address' => $this->limit(OpenDeliveries::addressOf($transaction)),
            'client' => $this->limit(OpenDeliveries::clientName(
                $transaction->client_firstname ?? null,
                $transaction->client_lastname ?? null
            )),
        ];

        if (!$snapshot) {
            DeliverySnapshot::create([
                'transaction_id' => $transactionId,
                'spot_poster_id' => $fields['spot_poster_id'],
                'courier_id' => $courierId,
                'status' => $status,
                'processing_status' => $processing,
            ]);

            // An unaccepted receipt gets no event of its own: the incoming_order
            // webhook reports it, and "opened" follows once the register accepts.
            $type = $status === self::STATUS_OPEN ? DeliveryEvent::TYPE_OPENED : $this->typeForStatus($status);

            if ($type) {
                $this->event($type, $at, $fields);
            }

            return;
        }

        $wasOpen = (int) $snapshot->status === self::STATUS_OPEN;

        if (!$wasOpen && $status === self::STATUS_OPEN) {
            // Accepted at the register (0 → 1), or reopened.
            $this->event(DeliveryEvent::TYPE_OPENED, $at, $fields);
        }

        if ($wasOpen && $status !== self::STATUS_OPEN && ($type = $this->typeForStatus($status))) {
            $this->event($type, $at, $fields);
        }

        if ($wasOpen && $status === self::STATUS_OPEN) {
            $before = $snapshot->courier_id ? (int) $snapshot->courier_id : null;

            if ($before !== $courierId) {
                $type = !$before
                    ? DeliveryEvent::TYPE_COURIER_ASSIGNED
                    : (!$courierId ? DeliveryEvent::TYPE_COURIER_REMOVED : DeliveryEvent::TYPE_COURIER_CHANGED);

                $this->event($type, $at, $fields + ['previous_courier_id' => $before]);
            }

            $processingBefore = $snapshot->processing_status !== null ? (int) $snapshot->processing_status : null;

            if ($processing !== null && $processingBefore !== $processing) {
                $this->event(DeliveryEvent::TYPE_STATUS_CHANGED, $at, $fields);
            }
        }

        $snapshot->fill([
            'courier_id' => $courierId,
            'status' => $status,
            'processing_status' => $processing,
        ])->save();
    }

    /**
     * The receipt is no longer readable. Only a receipt we saw open gets an event;
     * an unknown one was never on anybody's screen.
     */
    private function markGone(int $transactionId, Carbon $at): void
    {
        $snapshot = DeliverySnapshot::where('transaction_id', $transactionId)->first();

        if (!$snapshot || (int) $snapshot->status !== self::STATUS_OPEN) {
            return;
        }

        $last = DeliveryEvent::where('transaction_id', $transactionId)->orderByDesc('id')->first();

        $this->event(DeliveryEvent::TYPE_REMOVED, $at, [
            'transaction_id' => $transactionId,
            'spot_poster_id' => $snapshot->spot_poster_id,
            'courier_id' => $snapshot->courier_id,
            'address' => $last->address ?? null,
            'client' => $last->client ?? null,
        ]);

        $snapshot->status = self::STATUS_REMOVED;
        $snapshot->save();
    }

    private function typeForStatus(int $status): ?string
    {
        return [
            self::STATUS_CLOSED => DeliveryEvent::TYPE_CLOSED,
            self::STATUS_REMOVED => DeliveryEvent::TYPE_REMOVED,
            self::STATUS_CANCELED => DeliveryEvent::TYPE_CANCELED,
        ][$status] ?? null;
    }

    /**
     * The transaction webhook fires for every receipt, dine-in included.
     */
    private function isDelivery(object $transaction): bool
    {
        if (isset($transaction->service_mode)) {
            return (int) $transaction->service_mode === OpenDeliveries::SERVICE_MODE_DELIVERY;
        }

        // A dine-in receipt may still carry an empty delivery object, and empty()
        // is never true for an object.
        $delivery = (array) ($transaction->delivery ?? []);

        return !empty($delivery['address1']) || !empty($delivery['delivery_time']);
    }

    private function event(string $type, Carbon $at, array $fields): void
    {
        DeliveryEvent::create(['type' => $type, 'happened_at' => $at] + $fields);
    }

    private function moment(?int $time): Carbon
    {
        // Anything that is not a plausible unix timestamp would stamp the event in
        // 1970 and push it out of the feed, so fall back to the arrival time.
        return $time && $time > 1000000000 ? Carbon::createFromTimestamp($time) : Carbon::now();
    }

    private function limit($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        // 23000 is the SQLSTATE class for integrity constraint violations.
        return (string) $e->getCode() === '23000';
    }

    private function pruneSometimes(): void
    {
        if (random_int(1, 200) !== 1) {
            return;
        }

        $cutoff = Carbon::now()->subDays(self::KEEP_DAYS);

        DeliveryEvent::where('happened_at', '<', $cutoff)->delete();
        DeliverySnapshot::where('updated_at', '<', $cutoff)->delete();
    }
}
