<?php

namespace Layerok\PosterPos\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Layerok\PosterPos\Classes\Couriers\CourierLedger;
use Layerok\PosterPos\Classes\Couriers\CourierOrders;
use Layerok\PosterPos\Classes\Couriers\CourierPayroll;
use Layerok\PosterPos\Classes\Couriers\PosterDeliveryStatus;
use Layerok\PosterPos\Classes\Couriers\StatusChangeException;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierState;
use Layerok\PosterPos\Models\CourierTask;

/**
 * The courier app's API under /api/courier. The app itself is static files
 * hosted on their own (courier-app/ in the repo root). See docs/courier-app.md.
 *
 * The app retries failed requests on its own, so every answer says which kind
 * of failure it is: 503 means "try again", 403/409/422 mean "stop and show why".
 */
class CourierAppController
{
    public function orders(Request $request): JsonResponse
    {
        try {
            $orders = (new CourierOrders())->forCourier($this->courier($request));
        } catch (\Throwable $e) {
            Log::warning('[COURIER] orders failed', ['error' => $e->getMessage()]);

            return $this->error('Poster не отвечает. Повторяем…', 503);
        }

        return response()->json([
            'orders' => array_map(fn($o) => array_diff_key($o, ['courier_poster_id' => true]), $orders),
            'server_time' => OpenDeliveries::now()->format('Y-m-d H:i'),
        ]);
    }

    public function status(Request $request, string $id): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->error('Заказ не найден.', 404);
        }

        $target = (int) $request->input('status');

        try {
            $transaction = (new PosterDeliveryStatus())->change($this->courier($request), (int) $id, $target);
        } catch (StatusChangeException $e) {
            return $this->error($e->getMessage(), $e->httpStatus());
        }

        $order = (new CourierOrders())->present($transaction);

        return response()->json(['order' => array_diff_key($order, ['courier_poster_id' => true])]);
    }

    public function history(Request $request): JsonResponse
    {
        $date = (string) $request->query('date', OpenDeliveries::now()->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->error('Неверная дата.', 422);
        }

        try {
            $items = (new CourierLedger())->history($this->courier($request), $date, $date);
        } catch (\Throwable $e) {
            Log::warning('[COURIER] history failed', ['error' => $e->getMessage()]);

            return $this->error('Poster не отвечает. Повторяем…', 503);
        }

        return response()->json([
            'date' => $date,
            'items' => $items->map(fn($item) => array_diff_key($item, [
                'spot_poster_id' => true, 'spot_id' => true, 'source' => true, 'type_code' => true,
            ]))->values(),
        ]);
    }

    /**
     * What the app knows and Poster doesn't: the order being delivered right now
     * («Везу сейчас») and whether the courier is heading back to the spot. Shown
     * to admins on the delivery map and the couriers page. The app sends the
     * whole state each time, so the last report wins.
     */
    public function reportState(Request $request): JsonResponse
    {
        $courier = $this->courier($request);
        $current = $request->input('current_transaction_id');
        $current = is_scalar($current) && ctype_digit((string) $current) ? (string) $current : null;

        // Only one of the courier's own open orders can be "current"; anything
        // else (a stale id after a reassignment) is stored as none.
        if ($current !== null) {
            try {
                $mine = array_column((new CourierOrders())->forCourier($courier), 'id');
            } catch (\Throwable $e) {
                return $this->error('Poster не отвечает. Повторяем…', 503);
            }

            if (!in_array($current, $mine, true)) {
                $current = null;
            }
        }

        CourierState::updateOrCreate(
            ['courier_id' => $courier->id],
            [
                'current_transaction_id' => $current,
                'returning' => (bool) $request->input('returning'),
                'reported_at' => Carbon::now(),
            ]
        );

        return response()->json(['current_transaction_id' => $current]);
    }

    /**
     * Довоз / Закупка / Магазин. The app sends its own id with each one, so the
     * same trip retried after a lost answer is stored once.
     */
    public function createTask(Request $request): JsonResponse
    {
        $courier = $this->courier($request);
        $uuid = (string) $request->input('client_uuid');

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return $this->error('Нет идентификатора поездки.', 422);
        }

        $existing = CourierTask::withTrashed()->where('client_uuid', $uuid)->first();

        if ($existing) {
            return (int) $existing->courier_id === (int) $courier->id
                ? response()->json(['task' => $this->presentTask($existing)])
                : $this->error('Запрос отклонён.', 403);
        }

        if ((new CourierPayroll())->isClosed(OpenDeliveries::now()->toDateString())) {
            return $this->error('Сегодняшний день уже закрыт администратором. Сообщите ему о поездке.', 409);
        }

        if (!array_key_exists((string) $request->input('type'), CourierTask::TYPES)) {
            return $this->error('Выберите тип поездки.', 422, ['type' => ['Выберите тип поездки.']]);
        }

        $task = new CourierTask([
            'courier_id' => $courier->id,
            'spot_id' => $this->currentSpotId($courier),
            'type' => $request->input('type'),
            'details' => $this->text($request->input('details'), 255),
            'comment' => $this->text($request->input('comment'), 2000),
            'performed_at' => Carbon::now(),
            'source' => CourierTask::SOURCE_APP,
            'client_uuid' => strtolower($uuid),
        ]);

        try {
            $task->save();
        } catch (\October\Rain\Database\ModelException $e) {
            return $this->error($e->getMessage(), 422, $task->errors()->toArray());
        } catch (\Illuminate\Database\QueryException $e) {
            // Two retries of the same trip raced; the other one stored it.
            $existing = CourierTask::where('client_uuid', strtolower($uuid))->first();

            if ($existing) {
                return response()->json(['task' => $this->presentTask($existing)]);
            }

            throw $e;
        }

        return response()->json(['task' => $this->presentTask($task)], 201);
    }

    private function presentTask(CourierTask $task): array
    {
        $at = $task->performed_at->copy()->setTimezone(OpenDeliveries::TIMEZONE);

        return [
            'kind' => 'task',
            'id' => (string) $task->id,
            'client_uuid' => $task->client_uuid,
            'type' => $task->type_label,
            'date' => $at->toDateString(),
            'time' => $at->format('H:i'),
            'title' => (string) $task->details,
            'note' => (string) $task->comment,
            'can_return' => false,
        ];
    }

    /**
     * Couriers aren't tied to a spot, so an extra trip is filed under the spot
     * of the orders they hold right now, if any.
     */
    private function currentSpotId(Courier $courier): ?int
    {
        try {
            foreach ((new CourierOrders())->forCourier($courier) as $order) {
                if (!empty($order['spot']['id'])) {
                    return (int) $order['spot']['id'];
                }
            }
        } catch (\Throwable $e) {
            // The trip matters more than its spot; store it without one.
        }

        return null;
    }

    private function text($value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function courier(Request $request): Courier
    {
        return $request->attributes->get('courier');
    }

    private function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json(array_filter(['error' => $message, 'errors' => $errors]), $status);
    }
}
