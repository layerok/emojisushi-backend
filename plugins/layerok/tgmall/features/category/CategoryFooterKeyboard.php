<?php namespace Layerok\Tgmall\Features\Category;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;
use OFFLINE\Mall\Models\Category;
use Config;

class CategoryFooterKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $cart = $this->vars['cart'];
        $category_id = $this->vars['category_id'];
        $page = $this->vars['page'];

        $cart->refresh();

        $limit = Config::get('layerok.tgmall::settings.products.per_page', 10);

        if($limit < 1) {
            $limit = 1;
        }

        $all = Category::where('id', '=', $category_id)->first()->products;
        $count = $all->count();

        $lastPage = ceil($count / $limit);


        if ($lastPage > $page) {
            $this->append([
                'text' => self::lang('buttons.load_more'),
                'callback_data' => self::prepareCallbackData(
                    'category_item',
                    [
                        'id' => $category_id,
                        'page' => $page + 1
                    ]
                )
            ])->nextRow();
        }

        $cart_amount_text = $cart->products->count() ? " (" . $cart->products->count() . ")": '';
        $cart_text = self::lang('buttons.cart') . $cart_amount_text;

        $this
            ->append([
                'text' => $cart_text,
                'callback_data' => self::prepareCallbackData(
                    'cart',
                    [
                        'type' => 'list'
                    ]
                )
            ])
            ->nextRow()
            ->append([
                'text' => self::lang('buttons.to_categories'),
                'callback_data' => self::prepareCallbackData(
                    'category_items'
                )
            ])
            ->nextRow()
            ->append([
                'text' => self::lang('buttons.in_menu_main'),
                'callback_data' => self::prepareCallbackData(
                    'start',
                    []
                )
            ]);

    }
}
