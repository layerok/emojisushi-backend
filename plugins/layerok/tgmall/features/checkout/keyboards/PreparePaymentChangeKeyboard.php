<?php namespace Layerok\TgMall\Features\Checkout\Keyboards;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class PreparePaymentChangeKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this->append([
            'text' => 'Так',
            'callback_data' => self::prepareCallbackData(
                'prepare_payment_change'

            )
        ])->append([
            'text' => 'Ні',
            'callback_data' => self::prepareCallbackData(
                'list_delivery_methods'
            )
        ]);

    }
}
