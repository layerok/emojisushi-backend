<?php

namespace Layerok\Restapi\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Exception;
use October\Rain\Auth\AuthException;
use October\Rain\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

            $code = 500;

            if($exception instanceof \October\Rain\Database\ModelException) {
                $response['errors'] = $exception->getErrors();
                $code = 422;
            } else if ($exception instanceof ValidationException) {
                $response['errors'] = $exception->getErrors();
                $code = 422;
            } else if ($exception instanceof  \Illuminate\Validation\ValidationException) {
                $response['errors'] = $exception->errors();
                $code = $exception->status;
            } else if ($exception instanceof AuthException) {
                $code = 422;
                // todo: don't show to the user the detailed error
            }

            if($exception instanceof HttpException) {
                $code = $exception->getStatusCode();
            }

            //Log::error('[API error] ' . $exception);
            return response()->json($response, $code);
        });
    }
}
