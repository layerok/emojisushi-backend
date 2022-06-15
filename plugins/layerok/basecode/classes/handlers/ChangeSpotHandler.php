<?php

namespace Layerok\BaseCode\Classes\Handlers;


use Layerok\BaseCode\Classes\Traits\Lang;
use Layerok\BaseCode\Keyboards\SpotsKeyboard;
use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang as TgMallLang;
use Layerok\TgMall\Features\Index\MainMenuKeyboard;


class ChangeSpotHandler extends Handler
{
    use Lang, TgMallLang {
        Lang::lang insteadof TgMallLang;
        TgMallLang::lang as tgMallLang;
    }

    protected $name = "change_spot";

    public function run()
    {

        $id = $this->arguments[0];
        $this->getState()->setSpotId($id);
        $update = $this->getUpdate();
        $from = $update->getMessage()
            ->getChat();

        $spot = Spot::where('id', $id)->first();

        $response = $this->replyWithMessage([
            'chat_id' => $from->id,
            'text' => Lang::lang('spots.changed') . ': ' . $spot->name
        ]);

        $this->telegram->pinChatMessage([
            'chat_id' => $from->id,
            'message_id' => $response['message_id']
        ]);


        $text = sprintf(
            tgMallLang::lang('texts.welcome'),
            $from->getFirstName()
        );

        $markup = new MainMenuKeyboard();

        $this->replyWithMessage([
            'text' => $text,
            'reply_markup' => $markup->getKeyboard()
        ]);
    }
}
