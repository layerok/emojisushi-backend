<?php

namespace Layerok\TgMall\Features\Checkout\Messages;

use Layerok\TgMall\Classes\Messages\AbstractMessageHandler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\SticksKeyboard;

class OrderDeliveryAddressHandler extends AbstractMessageHandler
{
    use Lang;
    public function handle()
    {
        $this->state->setOrderInfoAddress($this->text);

        $k = new SticksKeyboard();
        // был выбран самовывоз
        $this->sendMessage([
            'text' => self::lang('texts.add_sticks_question'),
            'reply_markup' => $k->getKeyboard()
        ]);

        $this->state->setMessageHandler(null);
    }
}
