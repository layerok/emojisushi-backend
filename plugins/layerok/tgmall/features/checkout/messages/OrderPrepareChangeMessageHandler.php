<?php

namespace Layerok\TgMall\Features\Checkout\Messages;

use Layerok\TgMall\Classes\Callbacks\CallbackQueryBus;
use Layerok\TgMall\Classes\Messages\AbstractMessageHandler;
use Layerok\TgMall\Classes\Traits\Lang;

class OrderPrepareChangeMessageHandler extends AbstractMessageHandler
{
    use Lang;
    public function handle()
    {
        $this->state->setOrderInfoChange($this->text);

        CallbackQueryBus::instance()->make('list_delivery_methods', []);

        $this->state->setMessageHandler(null);
    }
}


