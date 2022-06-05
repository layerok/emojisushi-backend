<?php

namespace Layerok\TgMall\Classes\Keyboards;

use Telegram\Bot\Keyboard\Keyboard;

class SticksDialogKeyboard
{
    public static function getKeyboard():Keyboard
    {
        return YesNoKeyboard::getKeyboard(
            [
                'name' => 'yes_sticks',
            ],
            [
                'name' => 'comment_dialog'
            ]
        );
    }
}
