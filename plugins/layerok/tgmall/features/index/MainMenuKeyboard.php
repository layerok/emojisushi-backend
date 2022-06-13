<?php namespace Layerok\TgMall\Features\Index;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class MainMenuKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this
            ->append([
                'text' => self::lang('buttons.categories'),
                'callback_data' => self::prepareCallbackData('category_items', [])
            ])
            ->append([
                'text' => self::lang('buttons.cart'),
                'callback_data' => self::prepareCallbackData(
                    'cart',
                    ['type' => 'list']
                )
            ])
            ->nextRow()
            ->append([
                'text' => "Змінити заклад",
                'callback_data' => self::prepareCallbackData(
                    'change_spot',
                    ['type' => 'list']
                )
            ])
            ->append([
                'text' => '🌐 Вебсайт',
                'callback_data' => self::prepareCallbackData(
                    'website'
                )
            ]);
    }

}
