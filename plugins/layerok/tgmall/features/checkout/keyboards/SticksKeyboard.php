<?php namespace Layerok\TgMall\Features\Checkout\Keyboards;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class SticksKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this->append([
            'text' => self::lang('buttons.yes'),
            'callback_data' => self::prepareCallbackData(
                'yes_sticks'
            )
        ])->append([
            'text' => self::lang('buttons.no'),
            'callback_data' => self::prepareCallbackData(
                'wish_to_leave_comment'
            )
        ]);

    }
}
