<?php

use Layerok\Restapi\Http\Controllers\SpotController;
use Layerok\Restapi\Http\Middleware\ExceptionsMiddleware;
use \Layerok\Restapi\Http\Controllers\ProductController;
use \Layerok\Restapi\Http\Controllers\CategoryController;
use \Layerok\Restapi\Http\Controllers\CartController;
use \Layerok\Restapi\Http\Middleware\CustomSession;
use \Layerok\Restapi\Http\Controllers\ShippingMethodController;
use \Layerok\Restapi\Http\Controllers\PaymentMethodController;
use \Layerok\Restapi\Http\Controllers\WishlistController;

Route::group([
    'middleware' => [
        ExceptionsMiddleware::class,
        CustomSession::class,
    ],
    'prefix' => 'api'
], function () {
    Route::get('products', [ProductController::class, 'fetch']);
    Route::get('categories', [CategoryController::class, 'fetch']);
    Route::get('spots', [SpotController::class, 'fetch']);
    Route::get('shipping', [ShippingMethodController::class, 'all']);
    Route::get('payments', [PaymentMethodController::class, 'all']);


    Route::prefix('cart')->group(function() {
        Route::get('products', [CartController::class, 'all']);
        Route::get('add', [CartController::class, 'add']);
        Route::get('remove', [CartController::class, 'remove']);
    });

    Route::prefix('wishlist')->group(function() {
        Route::get('add', [WishlistController::class, 'add']);
    });
});
