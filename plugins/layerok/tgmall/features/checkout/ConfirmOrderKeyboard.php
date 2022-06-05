<?php

namespace Layerok\TgMall\Features\Checkout;
use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;

class ConfirmOrderKeyboard extends InlineKeyboard
{
    use CallbackData;
    public function build(): void
    {

        $this->append([
            'text' => 'Да',
            'callback_data' => self::prepareCallbackData('confirm_order')
        ])->append([
            'text' => 'Нет',
            'callback_data' => self::prepareCallbackData('start')
        ]);

    }

}
