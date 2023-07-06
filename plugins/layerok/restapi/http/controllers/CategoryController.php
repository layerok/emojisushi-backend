<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Classes\RootCategory;
use Layerok\PosterPos\Models\HideCategory;
use Layerok\PosterPos\Models\Spot;
use OFFLINE\Mall\Models\Category;

class CategoryController extends Controller
{
    public function fetch(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');
        $spot_slug_or_id = input('spot_slug_or_id');
        $spot = Spot::findBySlugOrId($spot_slug_or_id);

        $root = Category::where('slug', RootCategory::SLUG_KEY)->first();

        $query = Category::query()->with(['hide_categories_in_spot', 'image']);

        if($limit) {
            $query->limit($limit);
        }

        if($offset) {
            $query->offset($offset);
        }

        $records = $query->get()->filter(function(Category $category) use($spot) {
            $isHidden = $spot->hideCategories()->get()->search(function(Category $hiddenCategory) use($category) {
                return $hiddenCategory->id === $category->id;
            });
            return $isHidden === false;
        })
            ->values()
            ->toArray();


        return response()->json([
            'data' => $records,
            'meta' => [
                'total' => count($records),
                'offset' => $offset,
                'limit' => $limit
            ]
        ]);
    }
}
