<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Commands\Command;

class TgMallStartCommandStarting {
    use Lang;
    use HasSpot;

    public function onHandle(Command $command) {

        $update = $command->getUpdate();
        $message = $update->getMessage();
        $chat = $message->getChat();

        $stop = !$this->hasSpot($chat->id);

        return $stop;
    }

    public function subscribe($events)
    {
        $events->listen('tgmall.command.start.starting', self::class ."@onHandle");
    }
}
