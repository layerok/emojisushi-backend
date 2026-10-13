<?php

use Illuminate\Support\Facades\Route;
use Layerok\PosterPos\Classes\Couriers\AuthenticateCourier;
use Layerok\PosterPos\Controllers\CourierAppController;
use Layerok\PosterPos\Controllers\CourierAuthController;
use Layerok\PosterPos\Controllers\PosterWebhookController;
use Layerok\PosterPos\Controllers\WayForPayController;
use Layerok\PosterPos\Controllers\Addresses;

Route::post('/posterpos/webhook/handle', PosterWebhookController::class);
Route::post('/wayforpay-service-url', WayForPayController::class);
Route::post('/wayforpay-redirect', [WayForPayController::class, 'redirect']);

Route::get('test', function () {
    return explode('.', request()->header('host'))[0];
});

Route::prefix('api')->group(function () {
    Route::get('address/options', [Addresses::class, 'options']);
    Route::get('addresses', [Addresses::class, 'addresses']);

    Route::get('order/status', [WayForPayController::class, 'getOrderStatus']);

    // Courier app API. The app itself is hosted on its own (courier-app/ in the
    // repo root, at courier.emojisushi.com.ua) — see docs/courier-app.md.
    Route::prefix('courier')->group(function () {
        Route::post('auth/login', [CourierAuthController::class, 'login']);

        Route::middleware(AuthenticateCourier::class)->group(function () {
            Route::post('auth/logout', [CourierAuthController::class, 'logout']);
            Route::get('me', [CourierAuthController::class, 'me']);
            Route::get('orders', [CourierAppController::class, 'orders']);
            Route::post('orders/{id}/status', [CourierAppController::class, 'status']);
            Route::get('history', [CourierAppController::class, 'history']);
            Route::post('tasks', [CourierAppController::class, 'createTask']);
            Route::post('state', [CourierAppController::class, 'reportState']);
        });
    });
});
