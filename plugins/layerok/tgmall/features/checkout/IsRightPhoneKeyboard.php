<?php

namespace Layerok\TgMall\Features\Checkout;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class IsRightPhoneKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this->append([
            'text' => 'Да',
            'callback_data' => self::prepareCallbackData('list_payment_methods')
        ])->append([
            'text' => 'Нет',
            'callback_data' => self::prepareCallbackData('enter_phone')
        ]);
    }
}
