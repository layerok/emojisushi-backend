<?php namespace Layerok\TgMall\Features\Checkout\Keyboards;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class OrderConfirmedKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this->append([
            'text' => self::lang('buttons.in_menu_main'),
            'callback_data' => self::prepareCallbackData(
                'start'
            )
        ])->nextRow();
    }
}
