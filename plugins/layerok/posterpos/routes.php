<?php

use Illuminate\Support\Facades\Route;
use Layerok\PosterPos\Controllers\PosterWebhookController;
use Layerok\PosterPos\Controllers\WayForPayController;

Route::post('/posterpos/webhook/handle', PosterWebhookController::class);
Route::post('/wayforpay-service-url', WayForPayController::class);

Route::get('test', function () {
    return explode('.', request()->header('host'))[0];
});
