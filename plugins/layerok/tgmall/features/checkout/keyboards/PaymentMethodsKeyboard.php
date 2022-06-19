<?php

namespace Layerok\TgMall\Features\Checkout\Keyboards;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use OFFLINE\Mall\Models\PaymentMethod;

class PaymentMethodsKeyboard extends InlineKeyboard
{
    use CallbackData;

    public function build(): void
    {
        $methods = PaymentMethod::orderBy('sort_order', 'ASC')->get();

        $methods->map(function ($item) {
            $this->append([
                'text' => $item->name,
                'callback_data' => self::prepareCallbackData(
                    'chose_payment_method',
                    ['id' => $item->id]
                )
            ])->nextRow();
        });
    }
}
