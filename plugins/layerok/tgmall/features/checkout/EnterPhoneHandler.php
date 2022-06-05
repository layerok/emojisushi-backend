<?php

namespace Layerok\TgMall\Features\Checkout;

use Layerok\TgMall\Classes\Callbacks\Handler;

class EnterPhoneHandler extends Handler
{

    protected $name = "enter_phone";

    public function run()
    {
        $this->sendMessage([
            'text' => 'Введите Ваш телефон',
        ]);

        $this->getState()->setMessageHandler(OrderPhoneHandler::class);
    }
}
