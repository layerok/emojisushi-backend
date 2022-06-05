<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HelloController extends Controller
{
    public function greet(string $name): JsonResponse
    {
        return response()->json([
            'greet' => "Hello $name",
        ]);
    }
}
