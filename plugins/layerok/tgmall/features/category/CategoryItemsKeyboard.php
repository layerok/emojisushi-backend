<?php namespace Layerok\Tgmall\Features\Category;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;
use OFFLINE\Mall\Models\Category;


class CategoryItemsKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $categories = Category::all();

        $categories->map(function ($row) {

            $this->append(
                [
                    'text' => $row->name,
                    'callback_data' => self::prepareCallbackData(
                        'category_item',
                        [
                            'id' => $row->id,
                            'page' => 1
                        ]
                    )
                ]
            )->nextRow();
        });

        $this->append([
            'text' => self::lang('buttons.in_menu_main'),
            'callback_data' => self::prepareCallbackData('start')
        ]);
    }
}
