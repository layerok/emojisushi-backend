<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\BaseCode\Classes\Traits\Lang;
use Layerok\BaseCode\Keyboards\SpotsKeyboard;
use Layerok\TgMall\Classes\Webhook;

class TgMallStateCreated {
    use Lang;
    use HasSpot;

    public function onCreate(Webhook $webhook) {

        $chat_id =$webhook->getChatId();

        $stop = !$this->hasSpot($chat_id);

        if($webhook->handlerInfo) {
            if($webhook->handlerInfo[0] === 'change_spot') {
                return false;
            }
        }

        if($stop) {
            $k = new SpotsKeyboard();
            $webhook->sendMessage([
                'text' => self::lang('spots.choose'),
                'reply_markup' => $k->getKeyboard()
            ]);
        }

        return $stop;
    }

    public function subscribe($events)
    {
        $events->listen('tgmall.state.created', self::class ."@onCreate");
    }
}
