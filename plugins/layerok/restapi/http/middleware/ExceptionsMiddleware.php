<?php

namespace Layerok\Restapi\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Exception;
use October\Rain\Exception\ValidationException;
use Log;
use Config;
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
            $response = [];
            $response['message'] = $exception->getMessage();

            if ($exception instanceof ValidationException) {
                $response['errors'] = $exception->getErrors();
            }
            if ($exception instanceof  \Illuminate\Validation\ValidationException) {
                $response['errors'] = $exception->errors();
            }
            //Log::error('[API error] ' . $exception);
            return response()->json($response, 422);
        });
    }
}
