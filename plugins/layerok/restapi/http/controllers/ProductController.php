<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\Product;
use Request;

class ProductController extends Controller
{
    public function all(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');

        $query =  Product::with([
            'image_sets',
            'prices',
            'additional_prices'
        ])->where('published', '=', '1');

        if($limit) {
            $query->limit($limit);
        }

        if($offset) {
            $query->offset($offset);
        }

        $products = $query->get();

        return response()->json([
            'data' => $products->toArray(),
            'meta' => [
                'total' => $products->count(),
                'offset' => $offset,
                'limit' => $limit
            ]
        ]);
    }
}
