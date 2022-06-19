<?php namespace Layerok\BaseCode\Events;

use Layerok\BaseCode\Classes\Traits\HasSpot;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\PreparePaymentChangeKeyboard;
use OFFLINE\Mall\Models\PaymentMethod;

class TgMallPaymentMethodChosen {
    use Lang;
    use HasSpot;

    public function subscribe($events)
    {
        $events->listen('layerok.tgmall::payment_method_chosen', function(Handler $handler, $id) {
            $method = PaymentMethod::find($id);
            if ($method->code == 'cash') {
                // наличными
                $k = new PreparePaymentChangeKeyboard();
                $handler->sendMessage([
                    'text' => self::lang('texts.prepare_change_question'),
                    'reply_markup' => $k->getKeyboard()
                ]);
                $handler->getState()->setMessageHandler(null);
                return true;
            }

            return false;


        });
    }
}
