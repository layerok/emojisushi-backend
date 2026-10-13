<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Illuminate\Support\Facades\Log;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierState;
use Layerok\PosterPos\Models\Spot;
use poster\src\PosterApi;

/**
 * Moves a delivery order along in Poster: «Я поехал» sets 40, «Доставил» sets
 * 50, through transactions.updateTransaction.
 *
 * Checked on the test account: 30 → 40 and 40 → 50 are accepted; Poster refuses
 * to go back from 50 (error 32), so nothing here tries to.
 *
 * Failures are told apart because the app retries some and not others:
 *   409 — Poster or the order's state says no; retrying won't help
 *   403 — not this courier's order
 *   503 — Poster could not be reached; the app keeps retrying
 */
class PosterDeliveryStatus
{
    private const ALLOWED = [
        CourierOrders::IN_DELIVERY => [CourierOrders::READY],
        CourierOrders::DELIVERED => [CourierOrders::IN_DELIVERY],
    ];

    private OpenDeliveries $deliveries;

    public function __construct(?OpenDeliveries $deliveries = null)
    {
        $this->deliveries = $deliveries ?: new OpenDeliveries();
    }

    /**
     * @return object the receipt as Poster now has it
     * @throws StatusChangeException
     */
    public function change(Courier $courier, int $transactionId, int $target): object
    {
        if (!isset(self::ALLOWED[$target])) {
            throw new StatusChangeException('Недопустимый статус.', 422);
        }

        // Read live, never from the cache: the receipt may have been reassigned or
        // closed seconds ago.
        $transaction = $this->read($transactionId);

        if (!$transaction) {
            throw new StatusChangeException('Заказ не найден в Poster.', 409);
        }

        if (!$courier->poster_user_id || OpenDeliveries::courierIdOf($transaction) !== (int) $courier->poster_user_id) {
            throw new StatusChangeException('Этот заказ назначен другому курьеру.', 403);
        }

        if ((int) ($transaction->status ?? 0) !== OpenDeliveries::STATUS_OPEN) {
            throw new StatusChangeException('Заказ уже закрыт в Poster.', 409);
        }

        $current = (int) ($transaction->processing_status ?? 0);

        // The same request arriving twice (a retry after a lost answer) is fine.
        if ($current === $target) {
            return $transaction;
        }

        if (!in_array($current, self::ALLOWED[$target], true)) {
            throw new StatusChangeException($this->refusal($current, $target), 409);
        }

        $tablet = $this->tabletFor((int) ($transaction->spot_id ?? 0));

        $this->write([
            'spot_id' => (int) $transaction->spot_id,
            'spot_tablet_id' => $tablet,
            'transaction_id' => $transactionId,
            'processing_status' => $target,
        ]);

        Log::info('[COURIER] delivery status changed', [
            'courier_id' => $courier->id,
            'transaction_id' => $transactionId,
            'from' => $current,
            'to' => $target,
        ]);

        CourierOrders::forget();

        // A delivered order is nobody's "current" any more, even if the app's own
        // report of that is still on its way.
        if ($target === CourierOrders::DELIVERED) {
            CourierState::where('courier_id', $courier->id)
                ->where('current_transaction_id', $transactionId)
                ->update(['current_transaction_id' => null]);
        }

        $transaction->processing_status = $target;

        return $transaction;
    }

    private function read(int $transactionId): ?object
    {
        try {
            return $this->deliveries->transaction($transactionId);
        } catch (\Throwable $e) {
            Log::warning('[COURIER] Poster read failed', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);

            throw new StatusChangeException('Poster не отвечает. Повторяем…', 503);
        }
    }

    private function write(array $params): void
    {
        try {
            PosterApi::init(config('poster'));
            $result = PosterApi::makeApiRequest('transactions.updateTransaction', 'post', $params);
        } catch (\Throwable $e) {
            Log::warning('[COURIER] Poster write failed', $params + ['error' => $e->getMessage()]);

            throw new StatusChangeException('Poster не отвечает. Повторяем…', 503);
        }

        $result = is_object($result) ? $result : (object) (array) $result;

        // Success is only an explicit error_code 0 inside "response".
        if (isset($result->response) && (int) (((object) $result->response)->error_code ?? -1) === 0) {
            return;
        }

        // Poster answered, and said no: e.g. {"error":32,"message":"processing_status is undefined"}.
        if (isset($result->error)) {
            Log::warning('[COURIER] Poster refused status change', $params + ['answer' => $result]);

            throw new StatusChangeException('Poster не принял изменение: ' . ($result->message ?? $result->error), 409);
        }

        // No recognisable answer at all — treat as not reached, so it is retried.
        throw new StatusChangeException('Poster не ответил. Повторяем…', 503);
    }

    private function tabletFor(int $spotPosterId): int
    {
        // Two of our spots can share one Poster spot; any of them with a tablet
        // gives the register Poster expects.
        $spot = Spot::with('tablet')
            ->where('poster_id', $spotPosterId)
            ->whereNotNull('tablet_id')
            ->orderByDesc('published')
            ->get()
            ->first(fn(Spot $s) => $s->tablet && $s->tablet->tablet_id);

        if (!$spot) {
            Log::error('[COURIER] no tablet for Poster spot', ['spot_poster_id' => $spotPosterId]);

            throw new StatusChangeException('Для этой точки не настроен терминал. Сообщите администратору.', 409);
        }

        return (int) $spot->tablet->tablet_id;
    }

    private function refusal(int $current, int $target): string
    {
        if ($target === CourierOrders::IN_DELIVERY && $current < CourierOrders::READY) {
            return 'Заказ ещё готовится.';
        }

        if ($current >= CourierOrders::DELIVERED) {
            return 'Заказ уже отмечен доставленным.';
        }

        return 'Сначала отметьте «Я поехал».';
    }
}
