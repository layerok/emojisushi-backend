<?php namespace Layerok\BaseCode\Events;

use Layerok\Basecode\Classes\Receipt;
use Layerok\PosterPos\Classes\PosterProducts;
use Layerok\PosterPos\Classes\PosterUtils;
use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Classes\Callbacks\CallbackQueryBus;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Utils\CheckoutUtils;
use Log;
use poster\src\PosterApi;
use Telegram\Bot\Api;

class TgMallOrderHandler {
    use Lang;

    public function subscribe($events)
    {

        $events->listen('tgmall.order.preconfirm.receipt', function(Handler $handler) {
            $state = $handler->getState();
            $customer = $handler->getCustomer();
            $cart = $handler->getCart();
            $products = CheckoutUtils::getProducts($cart, $state);
            $phone = CheckoutUtils::getPhone($customer);
            $firstName = CheckoutUtils::getFirstName($customer);
            $lastName = CheckoutUtils::getLastName($customer);
            $address = CheckoutUtils::getCLientAddress($state);
            $change = CheckoutUtils::getChange($state);
            $comment = CheckoutUtils::getComment($state);
            $payment_method_name = CheckoutUtils::getPaymentMethodName($state);
            $delivery_method_name = CheckoutUtils::getDeliveryMethodName($state);
            $sticks = $state->getOrderInfoSticksCount();
            $spot_id = $state->getSpotId();
            $spot = Spot::find($spot_id);

            $posterProducts = new PosterProducts();

            $posterProducts
                ->addCartProducts($products)
                ->addProduct(
                    492,
                    $this->t('sticks_name'),
                    $sticks
                );

            return $this->buildReceipt([
                'headline' =>  $this->t('confirm_order_question'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'delivery_method_name' => $delivery_method_name,
                'payment_method_name' => $payment_method_name,
                'address' => $address,
                'change' => $change,
                'comment' => $comment,
                'products' => $posterProducts->all(),
                'total' => $cart->getTotalFormattedPrice(),
                'spot_name' => $spot->name,
                'target' => $this->t('bot')
            ]);
        });

        $events->listen('tgmall.order.confirmed', function(Handler $handler) {
            $state = $handler->getState();
            $customer = $handler->getCustomer();
            $cart = $handler->getCart();
            $products = CheckoutUtils::getProducts($cart, $state);
            $phone = CheckoutUtils::getPhone($customer);
            $firstName = CheckoutUtils::getFirstName($customer);
            $lastName = CheckoutUtils::getLastName($customer);
            $address = CheckoutUtils::getCLientAddress($state);
            $change = CheckoutUtils::getChange($state);
            $comment = CheckoutUtils::getComment($state);
            $payment_method_name = CheckoutUtils::getPaymentMethodName($state);
            $delivery_method_name = CheckoutUtils::getDeliveryMethodName($state);
            $sticks = $state->getOrderInfoSticksCount();
            $spot_id = $state->getSpotId();
            $spot = Spot::find($spot_id);

            if (!count($products) > 0) {
                $handler->sendMessage([
                    'text' => \Lang::get('layerok.tgmall::lang.telegram.texts.cart_is_empty'),
                ]);
                return false;
            }

            $posterProducts = new PosterProducts();

            $posterProducts
                ->addCartProducts($products)
                ->addProduct(
                    492,
                    $this->t('sticks_name'),
                    $sticks
                );

            $poster_comment = PosterUtils::getComment([
                'comment' => $comment,
                'payment_method_name' => $payment_method_name,
                'delivery_method_name' => $delivery_method_name,
                'change' => $change
            ],  function($key) {
                return $this->t($key);
            });

            $tablet_id = $spot->tablet->tablet_id ?? env('POSTER_FALLBACK_TABLET_ID');

            if(env('POSTER_SEND_ORDER_ENABLED')) {
                $result = $this->sendPoster([
                    'spot_id' => $tablet_id,
                    'phone' => $phone,
                    'products' => $posterProducts->all(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'comment' => $poster_comment,
                    'address' => $address
                ]);

                if (isset($result->error)) {
                    $poster_err =  $result->message;

                    $handler->sendMessage([
                        'text' => $poster_err
                    ]);

                    Log::error($poster_err);
                    return false;
                }
            }

            $token = optional($spot->bot)->token ?? env('TELEGRAM_FALLBACK_BOT_TOKEN');
            $chat_id = optional($spot->chat)->internal_id ?? env('TELEGRAM_FALLBACK_CHAT_ID');
            $api = new Api($token);

            $receipt = $this->buildReceipt([
                'headline' =>  $this->t('new_order'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'delivery_method_name' => $delivery_method_name,
                'payment_method_name' => $payment_method_name,
                'address' => $address,
                'change' => $change,
                'comment' => $comment,
                'products' => $posterProducts->all(),
                'total' => $cart->getTotalFormattedPrice(),
                'spot_name' => $spot->name,
                'target' => $this->t('bot')
            ]);

            $api->sendMessage([
                'text' => $receipt->getText(),
                'parse_mode' => "html",
                'chat_id' => $chat_id
            ]);


            return true;
        });
    }

    /**
     * @param $params = [
     * @var mixed headline
     * @var mixed first_name
     * @var mixed last_name
     * @var mixed phone
     * @var mixed delivery_method_name
     * @var mixed address
     * @var mixed payment_method_name
     * @var mixed change
     * @var mixed comment
     * @var array products
     * @var mixed total
     * @var mixed spot_name
     * @var mixed target
     *
     * ]
     *
     *
     * @return Receipt
     */

    public function buildReceipt($params): Receipt {
        $receipt = $this->getReceipt();
        return $receipt
            ->headline($params['headline'])
            ->field('first_name', $params['first_name'])
            ->field('last_name', $params['last_name'])
            ->field('phone', $params['phone'])
            ->field('delivery_method_name', $params['delivery_method_name'])
            ->field('address', $params['address'])
            ->field('payment_method_name', $params['payment_method_name'])
            ->field('change', $params['change'])
            ->field('comment', $params['comment'])
            ->newLine()
            ->products($params['products'])
            ->newLine()
            ->field('total', $params['total'])
            ->field('spot', $params['spot_name'])
            ->field('target', $params['target']);
    }

    public function sendPoster($data)
    {
        PosterApi::init();
        return (object)PosterApi::incomingOrders()
            ->createIncomingOrder($data);
    }


    public function t($key) {
        return \Lang::get('layerok.tgmall::lang.telegram.receipt.' . $key);
    }

    public function getReceipt(): Receipt
    {
        $receipt = new Receipt();

        $receipt->setProductNameResolver(function($product) {
            return $product['name'];
        });
        $receipt->setProductCountResolver(function($product) {
            return $product['count'];
        });

        $receipt->setTransResolver(function($key) {
            return $this->t($key);
        });

        return $receipt;
    }

}
