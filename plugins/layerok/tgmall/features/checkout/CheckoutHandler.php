<?php

namespace Layerok\TgMall\Features\Checkout;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;

class CheckoutHandler extends Handler
{
    use Lang;

    protected $name = "checkout";

    public function run()
    {
        // Очищаем инфу о заказе при начале оформления заказа
        $this->getState()->setOrderInfo([]);

        $this->replyWithMessage([
            'text' => "Введите Ваше имя"
        ]);

        $this->getState()->setMessageHandler(OrderNameHandler::class);
    }
}
