<?php

namespace Layerok\Restapi\Events;

use Layerok\Basecode\Classes\Receipt;
use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\CartProduct;
use Layerok\PosterPos\Models\Order;
use Layerok\PosterPos\Models\Spot;
use OFFLINE\Mall\Classes\Utils\Money;
use OFFLINE\Mall\Models\Currency;
use Telegram\Bot\Api;

class RestApiSendOrderToTelegram {
    public function subscribe($events) {
        $events->listen('restapi::order.created', function(Order $order, Cart $cart, Spot $spot) {
            $receiptProducts = $cart->products()->get()->map(function (CartProduct $cartProduct) {
                $item = [];
                $product = $cartProduct->product()->first();
                $item['name'] = $product['name'];
                $item['count'] = $cartProduct['quantity'];
                return $item;
            });

            $token = optional($spot->bot)->token ?? env('TELEGRAM_FALLBACK_BOT_TOKEN');
            $chat_id = optional($spot->chat)->internal_id ?? env('TELEGRAM_FALLBACK_CHAT_ID');
            $api = new Api($token);

            $money = app()->make(Money::class);
            $receipt = $this->getReceipt();
            $receipt
                ->headline('Нове замовлення')
                ->field('first_name', $order->customer->firstname)
                ->field('last_name', $order->customer->lastname)
                ->field('phone', $order->customer->user->phone)
                ->field('delivery_method_name', $order->shipping['method']['name'])
                ->field('address', $order->shipping_address['lines'])
                ->field('payment_method_name', $order->payment['method']['name'])
                ->field('change', $order->change)
                ->field('comment', $order->customer_notes)
                ->newLine()
                ->products($receiptProducts)
                ->newLine()
                ->field('total', $money->format(
                    $cart->totals()->totalPostTaxes(),
                    null,
                    Currency::$defaultCurrency
                ))
                ->field('spot', $spot->name)
                ->field('target', $receipt->trans('site'));

            $api->sendMessage([
                'text' => $receipt->getText(),
                'parse_mode' => "html",
                'chat_id' => $chat_id
            ]);
        });
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
            return $key;
        });

        return $receipt;
    }

}
