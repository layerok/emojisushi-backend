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
        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];
        PosterApi::init($config);
        $result = (object)PosterApi::makeApiRequest('clients.getPromotions', 'get');
        return response()->json($result->response);
    }
}
