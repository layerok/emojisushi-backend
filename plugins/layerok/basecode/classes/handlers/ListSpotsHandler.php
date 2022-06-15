<?php

namespace Layerok\BaseCode\Classes\Handlers;

use Layerok\BaseCode\Classes\Traits\Lang;
use Layerok\BaseCode\Keyboards\SpotsKeyboard;
use Layerok\TgMall\Classes\Callbacks\Handler;


class ListSpotsHandler extends Handler
{
    use Lang;

    protected $name = "list_spots";

    public function run()
    {


        $k = new SpotsKeyboard();
        $this->replyWithMessage([
            'text' => self::lang('spots.choose'),
            'reply_markup' => $k->getKeyboard()
        ]);
    }
}
