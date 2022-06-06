<?php

namespace Layerok\Restapi\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Exception;

class ExceptionsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $this->registerExceptionHandlers();

        return $next($request);
    }

    private function registerExceptionHandlers(): void
    {
        App::error(function (Exception $exception) {
            return response()->json([
                'error'  => $exception->getMessage()
            ], 422);
        });
    }
}
