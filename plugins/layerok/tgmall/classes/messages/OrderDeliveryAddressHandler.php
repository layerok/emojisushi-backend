<?php

namespace Layerok\TgMall\Classes\Messages;

use Layerok\TgMall\Classes\Keyboards\SticksDialogKeyboard;
use Layerok\TgMall\Classes\Traits\Lang;

class OrderDeliveryAddressHandler extends AbstractMessageHandler
{
    use Lang;
    public function handle()
    {
        $this->state->setOrderInfoAddress($this->text);

        $this->telegram->sendMessage([
            'text' => self::lang('texts.add_sticks_question'),
            'chat_id' => $this->update->getChat()->id,
            'reply_markup' => SticksDialogKeyboard::getKeyboard()
        ]);

        $this->state->setMessageHandler(null);
    }
}
