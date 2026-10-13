<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Illuminate\Support\Facades\DB;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierShift;

/**
 * Courier pay for one day:
 *   deliveries × PRICE_PER_DELIVERY + km × the courier's rate per km
 * where deliveries are the courier's closed Poster receipts (minus excluded
 * ones) plus their tasks.
 *
 * While the day is open everything is worked out live. Closing the day freezes
 * each courier's numbers, rate and item list into their shift row, and from then
 * on the day is read from those rows only.
 */
class CourierPayroll
{
    public const PRICE_PER_DELIVERY = 90;

    private CourierLedger $ledger;

    public function __construct(?CourierLedger $ledger = null)
    {
        $this->ledger = $ledger ?: new CourierLedger();
    }

    public function isClosed(string $date): bool
    {
        return CourierShift::where('date', $date)->whereNotNull('closed_at')->exists();
    }

    /**
     * @return array{closed: bool, closed_at: ?string, rows: array, unmapped: array, no_courier: int}
     */
    public function day(string $date): array
    {
        $shifts = CourierShift::with('courier')->where('date', $date)->get()->keyBy('courier_id');

        if ($shifts->contains(fn(CourierShift $s) => $s->isClosed())) {
            return $this->frozenDay($shifts);
        }

        $ledger = $this->ledger->forDate($date);

        $ids = collect(array_keys($ledger['couriers']))
            ->merge(array_keys($ledger['open']))
            ->merge($shifts->keys());

        $couriers = Courier::where('active', true)
            ->orWhereIn('id', $ids->unique()->all())
            ->orderBy('name')
            ->get();

        $rows = $couriers->map(function (Courier $courier) use ($ledger, $shifts) {
            $entry = $ledger['couriers'][$courier->id] ?? ['receipts' => [], 'tasks' => []];
            $shift = $shifts->get($courier->id);

            return $this->row(
                $courier->id,
                $courier->name,
                $entry['receipts'],
                $entry['tasks'],
                $shift ? (float) $shift->km : 0.0,
                (float) $courier->rate_per_km,
                self::PRICE_PER_DELIVERY
            ) + ['open_receipts' => $ledger['open'][$courier->id] ?? 0];
        })->values()->all();

        $unmapped = collect($ledger['unmapped'])->map(fn($receipts, $posterUserId) => [
            'poster_user_id' => (int) $posterUserId,
            'receipts' => count($receipts),
            'items' => CourierLedger::sort($receipts),
        ])->values()->all();

        return [
            'closed' => false,
            'closed_at' => null,
            'rows' => $rows,
            'unmapped' => $unmapped,
            'no_courier' => $ledger['no_courier'],
        ];
    }

    /**
     * Freeze the day. Couriers with nothing delivered and no km get no row.
     *
     * @return int number of couriers paid
     */
    public function close(string $date, ?int $userId): int
    {
        $day = $this->day($date);

        if ($day['closed']) {
            return 0;
        }

        $paid = 0;

        DB::transaction(function () use ($day, $date, $userId, &$paid) {
            foreach ($day['rows'] as $row) {
                if (!$row['deliveries'] && !$row['km']) {
                    continue;
                }

                CourierShift::updateOrCreate(
                    ['date' => $date, 'courier_id' => $row['courier_id']],
                    [
                        'km' => $row['km'],
                        'rate_per_km' => $row['rate_per_km'],
                        'price_per_delivery' => $row['price_per_delivery'],
                        'receipts_count' => $row['receipts'],
                        'tasks_count' => $row['tasks'],
                        'total' => $row['total'],
                        'items' => $row['items'],
                        'closed_at' => now(),
                        'closed_by' => $userId,
                    ]
                );

                $paid++;
            }
        });

        return $paid;
    }

    /**
     * Back to live numbers. The km typed in stay; the frozen totals are dropped.
     */
    public function reopen(string $date): void
    {
        CourierShift::where('date', $date)->update([
            'closed_at' => null,
            'closed_by' => null,
            'items' => null,
            'total' => null,
        ]);
    }

    public function saveKm(string $date, int $courierId, float $km): void
    {
        CourierShift::updateOrCreate(
            ['date' => $date, 'courier_id' => $courierId],
            ['km' => round($km, 2)]
        );
    }

    private function frozenDay($shifts): array
    {
        $rows = $shifts
            ->filter(fn(CourierShift $s) => $s->isClosed())
            ->map(fn(CourierShift $s) => $this->frozenRow($s))
            ->sortBy('name')
            ->values()
            ->all();

        $closedAt = $shifts->max('closed_at');

        return [
            'closed' => true,
            'closed_at' => $closedAt
                ? $closedAt->copy()->setTimezone(OpenDeliveries::TIMEZONE)->format('d.m.Y H:i')
                : null,
            'rows' => $rows,
            'unmapped' => [],
            'no_courier' => 0,
        ];
    }

    private function frozenRow(CourierShift $shift): array
    {
        // The driver may hand integer columns back as strings; the page compares
        // ids strictly, so everything is cast the way the live path produces it.
        $receipts = (int) $shift->receipts_count;
        $tasks = (int) $shift->tasks_count;
        $deliveries = $receipts + $tasks;
        $price = (float) $shift->price_per_delivery;
        $rate = (float) $shift->rate_per_km;
        $km = (float) $shift->km;

        return [
            'courier_id' => (int) $shift->courier_id,
            'name' => $shift->courier->name ?? ('#' . $shift->courier_id),
            'receipts' => $receipts,
            'tasks' => $tasks,
            'deliveries' => $deliveries,
            'price_per_delivery' => $price,
            'base' => round($deliveries * $price, 2),
            'km' => $km,
            'rate_per_km' => $rate,
            'km_pay' => round($km * $rate, 2),
            'total' => (float) $shift->total,
            'items' => $shift->items ?: [],
            'open_receipts' => 0,
        ];
    }

    private function row(int $courierId, string $name, array $receipts, array $tasks, float $km, float $rate, float $price): array
    {
        $paidReceipts = count(array_filter($receipts, fn($item) => !$item['excluded']));
        $deliveries = $paidReceipts + count($tasks);
        $base = round($deliveries * $price, 2);
        $kmPay = round($km * $rate, 2);

        return [
            'courier_id' => $courierId,
            'name' => $name,
            'receipts' => $paidReceipts,
            'tasks' => count($tasks),
            'deliveries' => $deliveries,
            'price_per_delivery' => $price,
            'base' => $base,
            'km' => $km,
            'rate_per_km' => $rate,
            'km_pay' => $kmPay,
            'total' => round($base + $kmPay, 2),
            'items' => CourierLedger::sort(array_merge($receipts, $tasks)),
        ];
    }
}
