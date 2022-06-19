<?php
namespace Layerok\TgMall\Features\Checkout\Keyboards;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use OFFLINE\Mall\Models\ShippingMethod;

class DeliveryMethodsKeyboard extends InlineKeyboard
{
    use CallbackData;

    public function build(): void
    {
        $methods = ShippingMethod::orderBy('sort_order', 'ASC')->get();

        $methods->map(function ($item) {
            $this->append([
                'text' => $item->name,
                'callback_data' => self::prepareCallbackData(
                    'chose_delivery_method',
                    ['id' => $item->id]
                )
            ])->nextRow();
        });
    }
}
