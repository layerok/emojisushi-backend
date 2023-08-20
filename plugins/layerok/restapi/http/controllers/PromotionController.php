<?php
declare(strict_types=1);

namespace Layerok\Restapi\Http\Controllers;


use Illuminate\Http\JsonResponse;
use poster\src\PosterApi;

/**
 *
 */
class PromotionController extends Controller
{

    public function list(): JsonResponse
    {
        PosterApi::init(config('poster'));
        $result = (object)PosterApi::makeApiRequest('clients.getPromotions', 'get');
        return response()->json($result->response);
    }
}
