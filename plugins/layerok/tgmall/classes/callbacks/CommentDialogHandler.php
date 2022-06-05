<?php

namespace Layerok\TgMall\Classes\Callbacks;

use Layerok\TgMall\Classes\Keyboards\LeaveCommentKeyboard;
use Layerok\TgMall\Classes\Traits\Lang;

class CommentDialogHandler extends Handler
{
    use Lang;

    protected $name = "comment_dialog";

    public function handle()
    {
        $this->telegram->sendMessage([
            'text' => self::lang('texts.leave_comment_question'),
            'chat_id' => $this->update->getChat()->id,
            'reply_markup' => LeaveCommentKeyboard::getKeyboard()
        ]);
    }
}
