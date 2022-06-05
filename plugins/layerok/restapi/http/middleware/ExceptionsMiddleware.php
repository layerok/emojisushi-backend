<?php

namespace Layerok\Restapi\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use October\Rain\Exception\ValidationException;

class ExceptionsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $this->registerExceptionHandlers();

        return $next($request);
    }

    private function registerExceptionHandlers(): void
    {
        App::error(function (ValidationException $exception) {
            return response()->json([
                'message' => 'validation exception',
                'errors'  => $exception->getErrors()
            ], 422);
        });
    }
}
