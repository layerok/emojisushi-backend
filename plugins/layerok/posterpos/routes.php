<?php

use Illuminate\Support\Facades\Route;
use Layerok\PosterPos\Controllers\PosterWebhookController;
use Layerok\PosterPos\Controllers\WayForPayController;
use poster\src\PosterApi;

Route::post('/posterpos/webhook/handle', PosterWebhookController::class);
Route::post('/wayforpay-service-url', WayForPayController::class);

Route::get('/poster-products', function() {
    PosterApi::init(config('poster'));
    echo '<ul>';
    foreach(PosterApi::menu()->getProducts()->response as $product) {
        echo "<li>"  . $product->product_id . " ". $product->product_name . "</li>";
    }
    echo '</ul>';
});

