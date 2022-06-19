<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\SticksKeyboard;
use Layerok\TgMall\Features\Checkout\Messages\OrderDeliveryAddressHandler;
use OFFLINE\Mall\Models\ShippingMethod;

class TgMallDeliveryMethodChosen{
    use Lang;
    use HasSpot;

    public function subscribe($events)
    {
        $events->listen('layerok.tgmall::delivery_method_chosen', function(Handler $handler, $id) {
            $method = ShippingMethod::find($id);
            if ($method->code === 'courier') {
                // доставка курьером
                $handler->sendMessage([
                    'text' => self::lang('texts.type_delivery_address'),
                ]);
                $handler->getState()->setMessageHandler(OrderDeliveryAddressHandler::class);

                return true;
            } else if($method->code === 'pickup') {
                $k = new SticksKeyboard();
                // был выбран самовывоз
                $handler->sendMessage([
                    'text' => self::lang('texts.add_sticks_question'),
                    'reply_markup' => $k->getKeyboard()
                ]);
                $handler->getState()->setMessageHandler(null);

                return true;
            }

            return false;


        });
    }
}
