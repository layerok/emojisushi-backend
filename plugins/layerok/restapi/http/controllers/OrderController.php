<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Spot;

class OrderController extends Controller
{
    public function place(): JsonResponse
    {
        $spot_id = input('spot_id');
        $all = request()->all();

        return response()->json([

        ]);
    }
}
