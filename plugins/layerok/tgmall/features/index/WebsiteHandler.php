<?php

namespace Layerok\TgMall\Features\Index;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;


class WebsiteHandler extends Handler
{
    use Lang;

    protected $name = "website";

    public function run()
    {
        $this->replyWithMessage([
            'text' => 'https://emojisushi.com.ua'
        ]);
    }
}
