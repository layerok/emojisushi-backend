<?php namespace  Layerok\Tgmall\Features\Category;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;
use OFFLINE\Mall\Models\Category;
use Telegram\Bot\Keyboard\Keyboard;
use Event;

class CategoryItemsHandler extends Handler
{
    use Lang;
    use CallbackData;

    protected $name = "category_items";


    public function run()
    {
        $query = Category::where('published', 1);

        Event::fire('tgmall.categories.query', [$query, $this]);

        $categories = $query->get();
        $markup = new CategoryItemsKeyboard([
            'categories' => $categories
        ]);
        $replyWith = [
            'text' => self::lang('texts.category'),
            'reply_markup' => $markup->getKeyboard()
        ];

        $this->replyWithMessage($replyWith);
    }
}
