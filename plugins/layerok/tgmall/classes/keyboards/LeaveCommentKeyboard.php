<?php
namespace Layerok\TgMall\Classes\Keyboards;

use Telegram\Bot\Keyboard\Keyboard;

class LeaveCommentKeyboard
{
    public static function getKeyboard(): Keyboard
    {
        return YesNoKeyboard::getKeyboard(
            [
                'name' => 'leave_comment',
            ],
            [
                'name' => 'pre_confirm_order'
            ]
        );
    }
}
