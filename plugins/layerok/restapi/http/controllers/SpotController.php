<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Spot;

class SpotController extends Controller
{
    public function fetch(): JsonResponse
    {
        $offset = input('offset');
        $limit = input('limit');

        $query = Spot::where('published', '=', '1');

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

    public function one(): JsonResponse {
        $slug_or_id = input('slug_or_id');

        $record = Spot::findBySlugOrId($slug_or_id);

        if(!$record) {
            return response()->json(['error' => 'Not Found!'], 404);
        }

        return response()->json($record);
    }
}
