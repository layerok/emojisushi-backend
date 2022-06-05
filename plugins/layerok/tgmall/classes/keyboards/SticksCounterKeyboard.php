<?php

namespace Layerok\TgMall\Classes\Keyboards;

use Layerok\TgMall\Classes\Buttons\CounterButtons;
use Layerok\TgMall\Classes\Buttons\TextButton;
use Telegram\Bot\Keyboard\Keyboard;

class SticksCounterKeyboard
{
    public static function getKeyboard($initialCount): Keyboard
    {
        $keyboard = new Keyboard();
        $keyboard->inline();
        $keyboard->row(...CounterButtons::getButtons(
            $initialCount,
            "update_sticks_counter"
        ));
        $keyboard->row(TextButton::getButton("Продолжить", "comment_dialog"));
        return $keyboard;
    }
}
