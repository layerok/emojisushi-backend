<?php

namespace Layerok\PosterPos\Classes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Layerok\PosterPos\Models\SpotStock;
use Layerok\PosterPos\Models\StockReservation;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Product;

/**
 * Limited per-spot stock.
 *
 * An order takes its quantities at placement, all or nothing. What it took is
 * recorded, so a failed payment or an order cancelled in Poster puts exactly that
 * back, once.
 */
class SpotStockService
{
    /**
     * Every limited product with what is left, by spot, for the spot payloads.
     *
     * @return array<int, array<int, array{product_id: int, amount: int}>> spot id => limits
     */
    public function limitsBySpot(): array
    {
        return SpotStock::orderBy('product_id')
            ->get(['spot_id', 'product_id', 'amount'])
            ->groupBy('spot_id')
            ->map(fn(Collection $rows) => $rows->map(fn(SpotStock $row) => [
                'product_id' => (int) $row->product_id,
                'amount' => max(0, (int) $row->amount),
            ])->values()->all())
            ->all();
    }

    /**
     * Product ids among the limits that ran out.
     *
     * @param array<int, array{product_id: int, amount: int}> $limits
     * @return int[]
     */
    public static function soldOut(array $limits): array
    {
        return collect($limits)->where('amount', '<=', 0)->pluck('product_id')->values()->all();
    }

    /**
     * Cart quantities summed per product, since a product can appear on several lines.
     *
     * @return array<int, int> product id => quantity
     */
    public function quantitiesFromCart(array $cart): array
    {
        return collect($cart['items'] ?? [])
            ->groupBy(fn($item) => (int) $item['id'])
            ->map(fn(Collection $items) => (int) $items->sum('quantity'))
            ->filter(fn($quantity) => $quantity > 0)
            ->all();
    }

    /**
     * Takes the ordered quantities of limited products from the spot's stock.
     * Nothing is taken unless every limited product has enough.
     *
     * @param array<int, int> $quantities product id => quantity
     * @return Collection<StockReservation>
     * @throws \UnderflowException when a limited product has too little left
     */
    public function reserve(int $spotId, array $quantities): Collection
    {
        $limited = SpotStock::where('spot_id', $spotId)
            ->whereIn('product_id', array_keys($quantities))
            ->pluck('product_id')
            ->map(fn($id) => (int) $id);

        if ($limited->isEmpty()) {
            return collect();
        }

        return DB::transaction(function () use ($spotId, $quantities, $limited) {
            $rows = collect();

            foreach ($limited as $productId) {
                $quantity = $quantities[$productId];

                // Conditional decrement: two orders racing for the last items cannot
                // both succeed, and the amount can never go below zero.
                $taken = SpotStock::where('spot_id', $spotId)
                    ->where('product_id', $productId)
                    ->where('amount', '>=', $quantity)
                    ->decrement('amount', $quantity);

                if (!$taken) {
                    // Rolls back whatever this order already took.
                    throw new \UnderflowException('Stock is short for product ' . $productId);
                }

                $rows->push(StockReservation::create([
                    'spot_id' => $spotId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'status' => StockReservation::STATUS_RESERVED,
                ]));
            }

            return $rows;
        }, 3);
    }

    /**
     * reserve() with the shortage turned into a message for the client.
     *
     * @param array<int, int> $quantities product id => quantity
     * @return Collection<StockReservation>
     */
    public function reserveOrFail(int $spotId, array $quantities): Collection
    {
        try {
            return $this->reserve($spotId, $quantities);
        } catch (\UnderflowException $e) {
            throw $this->shortError($spotId, $quantities);
        }
    }

    /**
     * Ties reservations made before the order existed to it.
     */
    public function link(Collection $reservations, array $attributes): void
    {
        if ($reservations->isEmpty()) {
            return;
        }

        StockReservation::whereIn('id', $reservations->pluck('id'))->update($attributes);
    }

    /**
     * Ties an online order's reservations to the Poster order it became, so a
     * cancellation in Poster can find them.
     */
    public function linkOnlineToIncoming(int $onlineOrderId, int $incomingOrderId): void
    {
        StockReservation::where('online_order_id', $onlineOrderId)
            ->update(['incoming_order_id' => $incomingOrderId]);
    }

    public function release(Collection $reservations): int
    {
        return $this->releaseQuery(StockReservation::whereIn('id', $reservations->pluck('id')));
    }

    public function releaseForOnlineOrder(int $onlineOrderId): int
    {
        return $this->releaseQuery(StockReservation::where('online_order_id', $onlineOrderId));
    }

    public function releaseForIncomingOrder(int $incomingOrderId): int
    {
        return $this->releaseQuery(StockReservation::where('incoming_order_id', $incomingOrderId));
    }

    public function hasReservedForIncomingOrder(int $incomingOrderId): bool
    {
        return StockReservation::where('incoming_order_id', $incomingOrderId)
            ->where('status', StockReservation::STATUS_RESERVED)
            ->exists();
    }

    /**
     * Puts reserved stock back. Each row is flipped to released on its own
     * condition first, so a retried callback or webhook cannot return it twice.
     * A product whose limit was removed meanwhile is simply not limited any more.
     */
    private function releaseQuery($query): int
    {
        $released = 0;

        $rows = $query->where('status', StockReservation::STATUS_RESERVED)->get();

        foreach ($rows as $row) {
            $flipped = StockReservation::where('id', $row->id)
                ->where('status', StockReservation::STATUS_RESERVED)
                ->update(['status' => StockReservation::STATUS_RELEASED, 'updated_at' => now()]);

            if (!$flipped) {
                continue;
            }

            SpotStock::where('spot_id', $row->spot_id)
                ->where('product_id', $row->product_id)
                ->increment('amount', $row->quantity);

            $released++;
        }

        return $released;
    }

    private function shortError(int $spotId, array $quantities): ValidationException
    {
        $stocks = SpotStock::where('spot_id', $spotId)
            ->whereIn('product_id', array_keys($quantities))
            ->get()
            ->keyBy('product_id');

        $short = $stocks->first(fn(SpotStock $stock) => $stock->amount < $quantities[$stock->product_id])
            ?? $stocks->first();

        $product = Product::find($short->product_id);

        return new ValidationException([
            'out_of_stock' => trans('layerok.restapi::validation.out_of_stock', [
                'product' => $product->name ?? ('#' . $short->product_id),
                'left' => max(0, (int) $short->amount),
            ]),
        ]);
    }
}
