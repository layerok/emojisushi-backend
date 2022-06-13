<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Classes\RootCategory;
use Layerok\PosterPos\Models\HideCategory;
use OFFLINE\Mall\Models\Category;

class CategoryController extends Controller
{
    public function fetch(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');

        $root = Category::where('slug', RootCategory::SLUG_KEY)->first();

        $hidden = HideCategory::where([
            'spot_id' => input('spot_id')
        ])->pluck('category_id');

        $query = Category::where([
            ['published', '=', '1'],
            ['parent_id', '=', $root->id],
        ])->whereNotIn('id', $hidden);



        if($limit) {
            $query->limit($limit);
        }

        if($offset) {
            $query->offset($offset);
        }

        $records = $query->get();

        return response()->json([
            'data' => $records->toArray(),
            'meta' => [
                'total' => $records->count(),
                'offset' => $offset,
                'limit' => $limit
            ]
        ]);
    }
}
