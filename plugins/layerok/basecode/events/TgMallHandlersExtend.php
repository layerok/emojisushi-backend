<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Handlers\ChangeSpotHandler;
use Layerok\BaseCode\Classes\Handlers\ListSpotsHandler;
use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\TgMall\Classes\Traits\Lang;

class TgMallHandlersExtend {
    use Lang;
    use HasSpot;

    public function onExtend(array $handlers) {
        $extended = [
            ChangeSpotHandler::class,
            ListSpotsHandler::class,
        ];

        return array_merge($handlers, $extended);
    }

    public function subscribe($events)
    {
        $events->listen('tgmall.handlers.extend', self::class ."@onExtend");
    }
}
