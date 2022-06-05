<?php

namespace Layerok\TgMall\Classes\Keyboards;


use Telegram\Bot\Keyboard\Keyboard;

class PreparePaymentChangeKeyboard
{
    public static function getKeyboard():Keyboard
    {
        return YesNoKeyboard::getKeyboard([
            'name' => 'prepare_payment_change'
        ],[
            'name' => 'list_delivery_methods'
        ]);
    }
}
