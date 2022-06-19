<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\SticksCounterKeyboard;


class UpdateSticksCounterHandler extends Handler
{
    use Lang;

    protected $name = "update_sticks_counter";

    public function run()
    {
        $count = $this->arguments[0];

        if($count < 0) {
            return;
        }

        $this->getState()->setOrderInfoSticksCount($count);

        $k = new SticksCounterKeyboard([
            'count' => $count
        ]);


        $this->editMessageReplyMarkup(
            $this->getTriggerMessageId(),
            [
                'reply_markup' => $k->getKeyboard()
            ]
        );
    }
}
