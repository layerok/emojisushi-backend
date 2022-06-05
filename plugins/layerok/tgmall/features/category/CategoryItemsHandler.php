<?php namespace  Layerok\Tgmall\Features\Category;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;
use OFFLINE\Mall\Models\Category;
use Telegram\Bot\Keyboard\Keyboard;

class CategoryItemsHandler extends Handler
{
    use Lang;
    use CallbackData;

    protected $name = "category_items";


    public function run()
    {
        $markup = new CategoryItemsKeyboard();
        $replyWith = [
            'text' => self::lang('texts.category'),
            'reply_markup' => $markup->getKeyboard()
        ];

        $this->replyWithMessage($replyWith);
    }
}
