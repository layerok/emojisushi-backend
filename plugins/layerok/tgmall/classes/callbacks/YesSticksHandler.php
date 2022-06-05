<?php

namespace Layerok\TgMall\Classes\Callbacks;

use Layerok\TgMall\Classes\Keyboards\SticksCounterReplyMarkup;
use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Keyboard\Keyboard;

class YesSticksHandler extends Handler
{
    use Lang;

    protected $name = "yes_sticks";

    public function handle()
    {
        $this->state->setOrderInfoSticksCount(1);
        $this->telegram->sendMessage([
            'text' => 'Добавьте желаемое кол-во палочек',
            'reply_markup' => SticksCounterReplyMarkup::getKeyboard(1),
            'chat_id' => $this->update->getChat()->id,
        ]);
    }
}
