<?php namespace Layerok\BaseCode\Events;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Utils\CheckoutUtils;
use Log;
use Telegram\Bot\Api;

class TgMallOrderHandler {
    use Lang;

    public function onConfirm(Handler $handler) {
        Log::info('onConfirm event occurred: ');

        $products = CheckoutUtils::getProducts($handler->getCart(), $handler->getState());
        $phone = CheckoutUtils::getPhone($handler->getCustomer());
        $firstName = CheckoutUtils::getFirstName($handler->getCustomer());
        $lastName = CheckoutUtils::getLastName($handler->getCustomer());

        $receipt = $handler->getReceipt();

        $receipt
            ->headline("Новый заказ!")
            ->field('first_name', $firstName)
            ->field('last_name', $lastName)
            ->field('phone', $phone)
            ->field('comment', $handler->getState()->getOrderInfoComment())
            ->field('delivery_method_name', CheckoutUtils::getDeliveryMethodName($handler->getState()))
            ->field('payment_method_name', CheckoutUtils::getPaymentMethodName($handler->getState()))
            ->newLine()
            ->products($products)
            ->newLine()
            ->field('total', $handler->getCart()->getTotalFormattedPrice());

            $this->sendTelegram($receipt->getText());

            return true;


    }

    public function sendTelegram($message)
    {

        $bot_token = env('TG_MALL_TEST_BOT_TOKEN');
        $chat_id = env('TG_MALL_TEST_CHAT_ID');


        if (empty($chat_id) || empty($bot_token)) {
            return;
        }

        $api = new Api($bot_token);

        try {
            $api->sendMessage([
                'text' => $message,
                'parse_mode' => "html",
                'chat_id' =>  $chat_id
            ]);
        } catch (\Exception $exception) {
            \Illuminate\Support\Facades\Log::error((string)$exception);
        }
    }

    public function subscribe($events)
    {
        $events->listen('tgmall.order.confirmed', self::class ."@onConfirm");
    }
}
