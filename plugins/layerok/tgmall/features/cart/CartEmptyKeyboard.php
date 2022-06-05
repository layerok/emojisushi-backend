<?php namespace Layerok\Tgmall\Features\Cart;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class CartEmptyKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this
            ->append([
            'text' => self::lang('buttons.to_categories'),
            'callback_data' => self::prepareCallbackData('category_items')
        ])
            ->nextRow()
            ->append([
            'text' => self::lang('buttons.in_menu_main'),
            'callback_data' => self::prepareCallbackData('start')
        ]);
    }

}
