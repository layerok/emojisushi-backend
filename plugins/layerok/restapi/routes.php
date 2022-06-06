<?php

use Layerok\Restapi\Http\Middleware\ExceptionsMiddleware;
use \Layerok\Restapi\Http\Controllers\ProductsController;

Route::group([
    'middleware' => ExceptionsMiddleware::class,
    'prefix' => 'api'
], function () {
    Route::get('products', [ProductsController::class, 'all']);
});
