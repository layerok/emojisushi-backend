<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\DeliveryMethodsKeyboard;
use Telegram\Bot\Keyboard\Keyboard;

class ListDeliveryMethodsHandler extends Handler
{
    use Lang;

    protected $name = "list_delivery_methods";

    public function run()
    {
        $k = new DeliveryMethodsKeyboard();
        $this->sendMessage([
            'text' => self::lang('texts.chose_delivery_method'),
            'reply_markup' => $k->getKeyboard(),
        ]);
    }
}
