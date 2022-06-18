<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\PosterPos\Models\HideProduct;
use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;

class TgMallProductsQuery{
    use Lang;
    use HasSpot;

    public function subscribe($events)
    {
        $events->listen('tgmall.products.query', function($query, Handler $handler) {
            $state = $handler->getState();
            $spot_id = $state->getSpotId();

            $spot = Spot::where('id', $spot_id)->first();

            $hidden = HideProduct::where([
                'spot_id' => $spot->id
            ])->pluck('product_id');

            $query->whereNotIn('offline_mall_products.id', $hidden);


        });
    }
}
