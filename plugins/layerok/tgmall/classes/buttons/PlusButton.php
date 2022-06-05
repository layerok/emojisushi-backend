<?php

namespace Layerok\TgMall\Classes\Buttons;

use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Keyboard\Keyboard;

class PlusButton
{
    use Lang;
    public static function getButton($handler = "noop", $arguments = [])
    {
        $keyboard = new Keyboard();
        return $keyboard::inlineButton([
            'text' => self::lang('buttons.plus'),
            'callback_data' => json_encode([
                'name' => $handler,
                'arguments' => $arguments
            ])
        ]);
    }
}
