<?php namespace Layerok\BaseCode\Events;
use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\BaseCode\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;


class TgMallKeyboardMainBeforeBuild {
    use HasSpot;
    use CallbackData;
    use Lang;

    public function beforeBuild(InlineKeyboard $keyboard) {
        $keyboard->listen('afterAppend', function($event, $params) use($keyboard) {
            $ci = $keyboard->getColumnIndex();
            $ri = $keyboard->getRowIndex();
            if($ri == 1 && $ci == 1) {
                $keyboard->nextRow()
                    ->append([
                        'text' => self::lang('spots.change'),
                        'callback_data' => self::prepareCallbackData(
                            'list_spots'
                        )
                    ]);

            };


        });
    }

    public function subscribe($events)
    {
        $events->listen('tgmall.keyboard.main.beforeBuild', self::class ."@beforeBuild");
    }
}
