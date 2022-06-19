<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Messages\OrderNameHandler;

class CheckoutHandler extends Handler
{
    use Lang;

    protected $name = "checkout";

    public function run()
    {
        // Очищаем инфу о заказе при начале оформления заказа
        $this->getState()->setOrderInfo([]);

        $this->replyWithMessage([
            'text' => self::lang('texts.type_your_name')
        ]);

        $this->getState()->setMessageHandler(OrderNameHandler::class);
    }
}
