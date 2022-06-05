<?php

namespace Layerok\TgMall\Classes\Callbacks;

use Layerok\TgMall\Classes\Messages\OrderPrepareChange;
use Layerok\TgMall\Classes\Traits\Lang;

class PreparePaymentChangeHandler extends Handler
{
    use Lang;

    protected $name = "prepare_payment_change";

    public function handle()
    {
        $this->telegram->sendMessage([
            'text' => self::lang('texts.payment_change'),
            'chat_id' => $this->update->getChat()->id
        ]);
        $this->state->setMessageHandler(OrderPrepareChange::class);
    }
}
