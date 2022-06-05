<?php

use Layerok\Restapi\Http\Middleware\ExceptionsMiddleware;

Route::group([
    'prefix'     => 'api',
    'middleware' => [
        ExceptionsMiddleware::class
    ]
], function () {
    Route::prefix('cart')->group(function() {

    });
});
