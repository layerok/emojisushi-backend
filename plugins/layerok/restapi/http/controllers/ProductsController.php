<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\Product;
use Request;

class ProductsController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'data' => $products->toArray(),
            'meta' => [
                'total' => $products->count()
            ]
        ]);
    }
}
