<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;

class ProductController extends Controller
{
    public function fetch(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');
        $category_slug = input('category_slug');

        $with = [
            'image_sets',
            'prices',
            'additional_prices',
        ];

        $query = Product::with($with)->where('published', '=', '1');

        if($category_slug) {
            $category = Category::where('slug', '=', $category_slug)->first();
            if($category) {
                $query->whereHas('categories', function ($q) use ($category) {
                    $q->whereIn('offline_mall_category_product.category_id', [$category->id]);
                });
            }
        }

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
