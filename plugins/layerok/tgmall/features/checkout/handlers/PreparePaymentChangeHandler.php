<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Messages\OrderPrepareChangeMessageHandler;


class PreparePaymentChangeHandler extends Handler
{
    use Lang;

    protected $name = "prepare_payment_change";

    public function run()
    {
        $this->sendMessage([
            'text' => self::lang('texts.payment_change'),
        ]);
        $this->getState()->setMessageHandler(OrderPrepareChangeMessageHandler::class);
    }
}



