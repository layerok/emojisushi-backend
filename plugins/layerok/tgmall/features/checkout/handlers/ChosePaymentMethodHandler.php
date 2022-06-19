<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\CallbackQueryBus;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Event;

class ChosePaymentMethodHandler extends Handler
{
    use Lang;

    protected $name = "chose_payment_method";

    public function run()
    {
        $id = $this->arguments['id'];
        $this->getState()->setOrderInfoPaymentMethodId($id);

        if($halt = Event::fire('layerok.tgmall::payment_method_chosen', [$this, $id])) {
            return;
        };

        CallbackQueryBus::instance()->make('list_delivery_methods', []);

    }
}
