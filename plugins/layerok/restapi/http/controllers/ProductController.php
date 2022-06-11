<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Wishlist;
use Session;

class ProductController extends Controller
{
    public function fetch(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');
        $category_slug = input('category_slug');
        $wishlist_only = input('wishlist');

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


        $wishlist = Wishlist::where('session_id', Session::get('wishlist_session_id'))
            ->first();
        $wishlist_ids = [];


        if($wishlist) {
            $items = $wishlist->items()->get();
            $wishlist_ids = $items->pluck('product_id')->toArray();
            if($wishlist_only) {
                $query->whereIn('id', $wishlist_ids);
            }
        } else {
            if($wishlist_only) {
                $query->whereIn('id', []);
            }
        }

        $total = $query->get()->count();

        if($limit) {
            $query->limit($limit);
        }

        if($offset) {
            $query->offset($offset);
        }

        $products = $query->get();

        $products->each(function($p, $i) use ($wishlist_ids, $products) {
            if(in_array($p->id, $wishlist_ids)) {
                $products[$i]->is_favorite_ = true;
            }
        });

        return response()->json([
            'data' => $products->toArray(),
            'meta' => [
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit
            ]
        ]);
    }
}
