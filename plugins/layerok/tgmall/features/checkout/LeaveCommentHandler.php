<?php

namespace Layerok\TgMall\Features\Checkout;

use Layerok\TgMall\Classes\Callbacks\Handler;

class LeaveCommentHandler extends Handler
{

    protected $name = "leave_comment";

    public function run()
    {
        $this->sendMessage([
            'text' => 'Комментарий к заказу',
        ]);
        $this
            ->getState()
            ->setMessageHandler(OrderCommentHandler::class);
    }
}
