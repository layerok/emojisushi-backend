<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Layerok\PosterPos\Classes\Delivery\DeliveryLocations;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\Spot;

/**
 * Open delivery orders as the courier app shows them.
 *
 * Every courier's app polls, so Poster is read once for all of them: the open
 * receipts of yesterday and today are presented, with map points from the same
 * code the admin map uses, and cached for a few seconds. Each courier gets
 * their own slice of that.
 */
class CourierOrders
{
    private const CACHE_KEY = 'courier-app:open-orders';
    private const CACHE_SECONDS = 20;

    // Poster processing_status
    public const READY = 30;
    public const IN_DELIVERY = 40;
    public const DELIVERED = 50;

    private OpenDeliveries $deliveries;

    public function __construct(?OpenDeliveries $deliveries = null)
    {
        $this->deliveries = $deliveries ?: new OpenDeliveries();
    }

    /**
     * The courier's open orders, delivered-but-not-yet-closed ones included so
     * the archive can offer to bring them back.
     */
    public function forCourier(Courier $courier): array
    {
        if (!$courier->poster_user_id) {
            return [];
        }

        return array_values(array_filter(
            $this->all(),
            fn(array $order) => $order['courier_poster_id'] === (int) $courier->poster_user_id
        ));
    }

    /**
     * Drop the cached read, so a courier sees their own status change at once.
     */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function present(object $transaction, array $point = ['lat' => null, 'lng' => null, 'source' => null]): array
    {
        $delivery = (object) ($transaction->delivery ?? []);
        $status = (int) ($transaction->processing_status ?? 0);
        $due = OpenDeliveries::dueAt($transaction);
        $now = OpenDeliveries::now();
        $spot = $this->spotFor((int) ($transaction->spot_id ?? 0));

        return [
            'id' => (string) $transaction->transaction_id,
            'courier_poster_id' => OpenDeliveries::courierIdOf($transaction),
            'processing_status' => $status,
            'state' => self::stateOf($status),
            'due' => $due ? $due->format('Y-m-d H:i') : null,
            'due_time' => $due ? $due->format('H:i') : null,
            'late_minutes' => $due && $now->gt($due) ? (int) $due->diffInMinutes($now) : 0,
            'address' => OpenDeliveries::addressOf($transaction),
            'place' => trim((string) ($delivery->comment ?? '')),
            'client' => OpenDeliveries::clientName($transaction->client_firstname ?? null, $transaction->client_lastname ?? null),
            'phone' => preg_replace('/[^+0-9]/', '', (string) ($transaction->client_phone ?? '')),
            'amount' => OpenDeliveries::amountOf($transaction),
            'payment' => self::paymentOf($transaction),
            'comment' => trim((string) ($transaction->transaction_comment ?? '')),
            'lat' => $point['lat'],
            'lng' => $point['lng'],
            // 'building' when the house was found, 'street' when only the street
            // is known — the same precision the admin map shows.
            'location_precision' => $point['source'],
            'spot' => $spot ? ['id' => $spot['id'], 'name' => $spot['name']] : null,
            // The spot's card for clients who pay by transfer, if one is set.
            'transfer_card' => $spot && $spot['transfer_card']
                ? ['number' => $spot['transfer_card'], 'point' => $spot['name']]
                : null,
        ];
    }

    public static function stateOf(int $processingStatus): string
    {
        if ($processingStatus >= self::DELIVERED) {
            return 'delivered';
        }

        if ($processingStatus === self::IN_DELIVERY) {
            return 'in_trip';
        }

        return $processingStatus >= self::READY ? 'ready' : 'preparing';
    }

    /**
     * What was paid how, in UAH. Poster keeps the amounts in kopecks; an order
     * paid on delivery has nothing paid yet, and the comment usually says how
     * the client will pay — the app reads that part.
     */
    public static function paymentOf(object $transaction): array
    {
        $uah = fn($field) => round(((int) ($transaction->{$field} ?? 0)) / 100, 2);

        return [
            'cash' => $uah('payed_cash'),
            'card' => $uah('payed_card'),
            'certificate' => $uah('payed_cert'),
            'bonus' => $uah('payed_bonus'),
            'thirdParty' => $uah('payed_third_party'),
            'wallet' => $uah('payed_ewallet'),
            'paid' => $uah('payed_sum'),
            'type' => (string) ($transaction->pay_type ?? ''),
        ];
    }

    /**
     * Every courier's open orders, from the shared cache. Each carries
     * 'courier_poster_id' so callers can split them by courier.
     */
    public function all(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        // Several couriers polling at the same moment would each read Poster and
        // geocode the same new addresses; one builds, the rest wait for it.
        return Cache::lock(self::CACHE_KEY . ':lock', 30)->block(15, function () {
            $cached = Cache::get(self::CACHE_KEY);

            if (is_array($cached)) {
                return $cached;
            }

            $orders = $this->build();
            Cache::put(self::CACHE_KEY, $orders, self::CACHE_SECONDS);

            return $orders;
        });
    }

    private function build(): array
    {
        $now = OpenDeliveries::now();

        $transactions = $this->deliveries
            ->openTransactions($now->copy()->subDay()->toDateString(), $now->toDateString())
            ->filter(fn($t) => OpenDeliveries::courierIdOf($t) !== null)
            ->values();

        $points = (new DeliveryLocations())->forTransactions($transactions);

        return $transactions
            ->map(fn($t) => $this->present($t, $points[(string) $t->transaction_id] ?? ['lat' => null, 'lng' => null, 'source' => null]))
            ->sortBy(fn($o) => $o['due'] ?? '9999')
            ->values()
            ->all();
    }

    private ?Collection $spots = null;

    /**
     * Our spot for a Poster spot id. Two of our spots can share one Poster spot;
     * the published one is preferred.
     */
    private function spotFor(int $posterId): ?array
    {
        $this->spots ??= Spot::whereNotNull('poster_id')
            ->orderByDesc('published')
            ->orderBy('id')
            ->get(['id', 'name', 'poster_id', 'published', 'transfer_card']);

        $matches = $this->spots->filter(fn(Spot $s) => (int) $s->poster_id === $posterId);
        $spot = $matches->first();

        if (!$spot) {
            return null;
        }

        // Of several spots sharing one Poster spot, the first with a card set.
        $card = optional($matches->first(fn(Spot $s) => !empty($s->transfer_card)))->transfer_card;

        return ['id' => $spot->id, 'name' => $spot->name, 'transfer_card' => $card];
    }
}
