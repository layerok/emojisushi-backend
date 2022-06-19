<?php

namespace Layerok\TgMall\Features\Checkout\Messages;

use Layerok\TgMall\Classes\Callbacks\CallbackQueryBus;
use Layerok\TgMall\Classes\Messages\AbstractMessageHandler;
use Layerok\TgMall\Classes\Traits\Lang;

class OrderCommentHandler extends AbstractMessageHandler
{
    use Lang;
    public function handle()
    {
        $this->state->setOrderInfoComment($this->text);

        CallbackQueryBus::instance()->make('pre_confirm_order', []);
    }
}
