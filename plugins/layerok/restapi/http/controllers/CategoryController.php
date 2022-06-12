<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Classes\RootCategory;
use OFFLINE\Mall\Models\Category;

class CategoryController extends Controller
{
    public function fetch(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');

        $root = Category::where('slug', RootCategory::SLUG_KEY)->first();

        $query = Category::where([
            ['published', '=', '1'],
            ['parent_id', '=', $root->id],
        ]);

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
