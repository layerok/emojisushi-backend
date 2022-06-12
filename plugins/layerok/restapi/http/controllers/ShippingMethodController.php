<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Spot;
use OFFLINE\Mall\Models\ShippingMethod;

class ShippingMethodController extends Controller
{
    public function all(): JsonResponse
    {

        $records = ShippingMethod::all();

        return response()->json([
            'data' => $records->toArray(),
            'meta' => [
                'total' => $records->count(),
            ]
        ]);
    }
}
