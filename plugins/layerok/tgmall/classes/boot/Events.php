<?php namespace Layerok\TgMall\Classes\Boot;

use Event;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Keyboards\PreparePaymentChangeKeyboard;
use Layerok\TgMall\Classes\Keyboards\SticksDialogKeyboard;
use Layerok\TgMall\Classes\Messages\OrderDeliveryAddressHandler;
use Layerok\TgMall\Classes\Traits\Lang;

class Events {
    use Lang;
    static function boot() {

        Event::listen('layerok.tgmall::delivery_method_chosen', function(Handler $handler, $id) {
            if ($id == 3) {
                // доставка курьером
                $handler->sendMessage([
                    'text' => self::lang('texts.type_delivery_address'),
                ]);
                $handler->getState()->setMessageHandler(OrderDeliveryAddressHandler::class);

                return true;
            } else if($id == 2) {
                // был выбран самовывоз
                $handler->sendMessage([
                    'text' => self::lang('texts.add_sticks_question'),
                    'reply_markup' => SticksDialogKeyboard::getKeyboard()
                ]);
                $handler->getState()->setMessageHandler(null);

                return true;
            }

            return false;



        });

        Event::listen('layerok.tgmall::payment_method_chosen', function(Handler $handler, $id) {
            if ($id == 4) {
                // наличными
                $handler->sendMessage([
                    'text' => self::lang('texts.prepare_change_question'),
                    'reply_markup' => PreparePaymentChangeKeyboard::getKeyboard()
                ]);
                $handler->getState()->setMessageHandler(null);
                return true;
            }

            return false;

        });

    }
}
