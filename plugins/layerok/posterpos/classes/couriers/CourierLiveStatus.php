<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierState;
use Layerok\PosterPos\Models\CourierToken;

/**
 * What each courier is doing right now, for admins.
 *
 * Poster says whether they are on a trip (orders at 40). The courier app adds
 * what Poster can't hold: the order being delivered right now and whether the
 * courier is heading back to the spot. Read once per request and shared by
 * every row that asks.
 */
class CourierLiveStatus
{
    private static ?array $snapshot = null;

    /**
     * @return array{
     *     state: string,      'ride' | 'back' | 'waiting' | 'free' | 'unknown'
     *     label: string,
     *     detail: string,
     *     current: ?array,    the order being delivered now: id, address, due_time
     *     seen: ?string,      when the courier last used the app, e.g. "5 мин назад"
     * }
     */
    public static function of(Courier $courier): array
    {
        $snapshot = self::snapshot();
        $state = $snapshot['states'][$courier->id] ?? null;
        $seen = self::seenText($snapshot['seen'][$courier->id] ?? null);

        $result = fn($s, $label, $detail = '', $current = null) => [
            'state' => $s, 'label' => $label, 'detail' => $detail, 'current' => $current, 'seen' => $seen,
        ];

        if ($snapshot['orders'] === null) {
            return $result('unknown', 'Нет связи с Poster');
        }

        if (!$courier->poster_user_id) {
            return $result('unknown', 'Не привязан к Poster');
        }

        $mine = array_values(array_filter(
            $snapshot['orders'],
            fn($o) => $o['courier_poster_id'] === (int) $courier->poster_user_id
        ));
        $inTrip = array_values(array_filter($mine, fn($o) => $o['state'] === 'in_trip'));
        $waiting = array_values(array_filter($mine, fn($o) => in_array($o['state'], ['ready', 'preparing'], true)));

        if ($inTrip) {
            $currentId = $state && $state->current_transaction_id ? (string) $state->current_transaction_id : null;
            $current = $currentId ? (array_values(array_filter($inTrip, fn($o) => $o['id'] === $currentId))[0] ?? null) : null;

            if ($current) {
                return $result('ride', 'Везёт заказ #' . $current['id'],
                    trim($current['address'] . ($current['due_time'] ? ' · до ' . $current['due_time'] : '')) .
                    (count($inTrip) > 1 ? ' · ещё ' . (count($inTrip) - 1) . ' в рейсе' : ''),
                    ['id' => $current['id'], 'address' => $current['address'], 'due_time' => $current['due_time']]);
            }

            return $result('ride', 'В рейсе', count($inTrip) . ' ' . self::orders(count($inTrip)) . ' · следующий не выбран');
        }

        if ($state && $state->returning) {
            return $result('back', 'Возвращается на точку');
        }

        if ($waiting) {
            // Poster only says the orders are assigned; that the courier is at the
            // spot waiting is something only the app could tell.
            return $result(
                'waiting',
                isset($snapshot['seen'][$courier->id]) ? 'На точке · ждёт выезда' : 'Не в приложении',
                count($waiting) . ' ' . self::orders(count($waiting)) . ' назначено'
            );
        }

        // Never signed in to the app: there's nothing to say beyond that.
        return $result('free', isset($snapshot['seen'][$courier->id]) ? 'На точке' : 'Не в приложении');
    }

    /**
     * The courier app's current order for each receipt being delivered right
     * now: transaction id => courier id.
     */
    public static function currentOrders(): array
    {
        return array_flip(array_map('strval', array_filter(
            array_map(fn(CourierState $s) => $s->current_transaction_id, self::snapshot()['states'])
        )));
    }

    private static function snapshot(): array
    {
        if (self::$snapshot !== null) {
            return self::$snapshot;
        }

        try {
            $orders = (new CourierOrders())->all();
        } catch (\Throwable $e) {
            Log::warning('[COURIER] live status: Poster read failed', ['error' => $e->getMessage()]);
            $orders = null;
        }

        return self::$snapshot = [
            'orders' => $orders,
            'states' => CourierState::all()->keyBy('courier_id')->all(),
            'seen' => CourierToken::query()
                ->selectRaw('courier_id, MAX(last_used_at) as seen')
                ->groupBy('courier_id')
                ->pluck('seen', 'courier_id')
                ->all(),
        ];
    }

    private static function seenText($seen): ?string
    {
        if (!$seen) {
            return null;
        }

        $at = Carbon::parse($seen, 'UTC');
        $minutes = (int) $at->diffInMinutes(Carbon::now('UTC'));

        if ($minutes < 10) {
            return 'в приложении сейчас';
        }

        if ($minutes < 60) {
            return 'в приложении ' . $minutes . ' мин назад';
        }

        return 'в приложении ' . $at->setTimezone(OpenDeliveries::TIMEZONE)->format('d.m H:i');
    }

    private static function orders(int $n): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 'заказ';
        }

        return $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) ? 'заказа' : 'заказов';
    }
}
