<?php

use Layerok\Restapi\Http\Middleware\ExceptionsMiddleware;
use \Layerok\Restapi\Http\Controllers\ProductController;
use \Layerok\Restapi\Http\Controllers\CategoryController;

Route::group([
    'middleware' => ExceptionsMiddleware::class,
    'prefix' => 'api'
], function () {
    Route::get('products', [ProductController::class, 'all']);
    Route::get('categories', [CategoryController::class, 'all']);
});
