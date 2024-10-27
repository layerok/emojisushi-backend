<?php

use Illuminate\Support\Facades\Route;
use Layerok\PosterPos\Controllers\PosterWebhookController;
use Layerok\PosterPos\Controllers\WayForPayController;
use Layerok\PosterPos\Models\PosterAccount;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\Variant;

Route::post('/posterpos/webhook/handle', PosterWebhookController::class);
Route::post('/wayforpay-service-url', WayForPayController::class);

Route::get('test', function () {
    return explode('.', request()->header('host'))[0];
});

