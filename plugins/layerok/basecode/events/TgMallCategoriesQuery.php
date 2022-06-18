<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Handlers\ChangeSpotHandler;
use Layerok\BaseCode\Classes\Handlers\ListSpotsHandler;
use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\PosterPos\Classes\RootCategory;
use Layerok\PosterPos\Models\HideCategory;
use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use OFFLINE\Mall\Models\Category;

class TgMallCategoriesQuery{
    use Lang;
    use HasSpot;

    public function subscribe($events)
    {
        $events->listen('tgmall.categories.query', function($query, Handler $handler) {
            $state = $handler->getState();
            $spot_id = $state->getSpotId();

            $spot = Spot::where('id', $spot_id)->first();

            $root = Category::where([
                ['slug', RootCategory::SLUG_KEY],

            ])->first();

            $hidden = HideCategory::where([
                'spot_id' => $spot->id
            ])->pluck('category_id');

            $query->where([
                ['parent_id', $root->id],
            ])->whereNotIn('id', $hidden);


        });
    }
}
