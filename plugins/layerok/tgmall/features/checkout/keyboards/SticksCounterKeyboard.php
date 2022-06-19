<?php

namespace Layerok\TgMall\Features\Checkout\Keyboards;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class SticksCounterKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {

        $count = $this->vars['count'];

        $this->append([
            'text' => self::lang('buttons.minus'),
            'callback_data' => self::prepareCallbackData(
                'update_sticks_counter',
                [$count - 1]
            )
        ])->append([
            'text' => $count,
            'callback_data' => self::prepareCallbackData('noop')
        ])->append([
            'text' => self::lang('buttons.plus'),
            'callback_data' => self::prepareCallbackData('update_sticks_counter', [$count + 1])
        ])->nextRow()->append([
            'text' => self::lang('buttons.next'),
            'callback_data' =>
                self::prepareCallbackData(
                    'wish_to_leave_comment'
                )
        ]);
    }
}
