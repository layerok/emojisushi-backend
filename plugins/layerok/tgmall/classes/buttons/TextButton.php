<?php

namespace Layerok\TgMall\Classes\Buttons;

use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Keyboard\Keyboard;

class TextButton
{
    use Lang;
    public static function getButton($text, $handler = "noop")
    {
        $keyboard = new Keyboard();
        return $keyboard::inlineButton([
            'text' => $text,
            'callback_data' => json_encode([
                'name' => $handler
            ])
        ]);
    }
}
