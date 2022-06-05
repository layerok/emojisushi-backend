<?php namespace Layerok\Tgmall\Features\Cart;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class CartFooterKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $cart = $this->vars['cart'];

        if ($cart->products->count() !== 0) {

            $total = self::lang('texts.all_amount_order', [
                    'price' => $cart->getTotalFormattedPrice()
                ]
            );

            $this->append([
                'text' => $total,
                'callback_data' => self::prepareCallbackData('noop')
            ])->nextRow()->append([
                'text' => self::lang('buttons.take_order'),
                'callback_data' => self::prepareCallbackData('checkout')
            ])->nextRow();
        }

        $this->append([
            'text' => self::lang('buttons.to_categories'),
            'callback_data' => self::prepareCallbackData('category_items')
        ])->nextRow()->append([
            'text' => self::lang('buttons.in_menu_main'),
            'callback_data' => self::prepareCallbackData('start')
        ]);

    }

}
