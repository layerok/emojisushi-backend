<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\SticksCounterKeyboard;

class YesSticksHandler extends Handler
{
    use Lang;

    protected $name = "yes_sticks";

    public function run() {

        $initialCount = 1;
        $this->getState()->setOrderInfoSticksCount($initialCount);

        $k = new SticksCounterKeyboard([
            'count' => $initialCount
        ]);

        $this->sendMessage([
            'text' => self::lang('texts.add_sticks'),
            'reply_markup' => $k->getKeyboard(),
        ]);
    }
}
