<?php

use Layerok\Restapi\Http\Controllers\SpotController;
use Layerok\Restapi\Http\Middleware\ExceptionsMiddleware;
use \Layerok\Restapi\Http\Controllers\ProductController;
use \Layerok\Restapi\Http\Controllers\CategoryController;

Route::group([
    'middleware' => ExceptionsMiddleware::class,
    'prefix' => 'api'
], function () {
    Route::get('products', [ProductController::class, 'fetch']);
    Route::get('categories', [CategoryController::class, 'fetch']);
    Route::get('spots', [SpotController::class, 'fetch']);

});
