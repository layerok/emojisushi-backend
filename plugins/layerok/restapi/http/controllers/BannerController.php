<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Banner;

class BannerController extends Controller
{
    public function all(): JsonResponse
    {
        $banners = Banner::with([
            'image',
            'image_small',
            'product',
            'product.categories'
        ])->where('is_active', true)->get();

        return response()->json([
            'data' => $banners->toArray(),
        ]);
    }


}
