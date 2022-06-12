<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Spot;
use OFFLINE\Mall\Models\PropertyGroup;

class IngredientController extends Controller
{
    public function all(): JsonResponse
    {
        $group = PropertyGroup::where('id', 1)->first(); // 1 - id группы "Ингридиенты"
        $records = $group->properties()->get();

        return response()->json([
            'data' => $records->toArray(),
            'meta' => [
                'total' => $records->count(),
            ]
        ]);
    }
}
