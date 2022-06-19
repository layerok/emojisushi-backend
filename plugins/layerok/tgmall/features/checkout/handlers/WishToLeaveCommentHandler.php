<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Keyboards\YesNoKeyboard;
use Layerok\TgMall\Classes\Traits\Lang;


class WishToLeaveCommentHandler extends Handler
{
    use Lang;

    protected $name = "wish_to_leave_comment";

    public function run()
    {

        $k = new YesNoKeyboard([
            'yes' => [
                'handler' => 'leave_comment',
            ],
            'no' => [
                'handler' => 'pre_confirm_order'
            ]
        ]);


        $this->sendMessage([
            'text' => self::lang('texts.leave_comment_question'),
            'reply_markup' => $k->getKeyboard()
        ]);
    }
}
