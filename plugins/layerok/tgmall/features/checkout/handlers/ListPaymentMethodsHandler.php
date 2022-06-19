<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\PaymentMethodsKeyboard;

class ListPaymentMethodsHandler extends Handler
{
    use Lang;

    protected $name = "list_payment_methods";


    public function run()
    {
        $k = new PaymentMethodsKeyboard();

        $this->sendMessage([
            'text' => self::lang('texts.chose_payment_method'),
            'reply_markup' => $k->getKeyboard()
        ]);
    }
}
