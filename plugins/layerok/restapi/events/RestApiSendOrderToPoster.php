<?php

namespace Layerok\Restapi\Events;
use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\Order;
use Layerok\PosterPos\Models\Spot;
use October\Rain\Exception\ValidationException;
use poster\src\PosterApi;

class RestApiSendOrderToPoster {
    public function subscribe($events) {
        $events->listen('restapi::order.created', function(Order $order, Cart $cart, Spot $spot) {
            $posterProducts = [];

            $products = $cart->products()->get();
            $customer = $order->customer;
            $user = $customer->user;

            foreach($products as $cartProduct) {
                $item = [];
                $product = $cartProduct->product()->first();
                $item['name'] = $product['name'];
                $item['count'] = $cartProduct['quantity'];
                $item['product_id'] = $product['poster_id'];
                if (isset($cartProduct['variant_id'])) {
                    $variant = $cartProduct->getItemDataAttribute();
                    $item['modificator_id'] = $variant['poster_id'];
                }
                $posterProducts[] = $item;
            }

            $tablet_id = $spot->tablet->tablet_id ?? env('POSTER_FALLBACK_TABLET_ID');

            PosterApi::init([
                    'access_token' => config('poster.access_token'),
                    'application_secret' => config('poster.application_secret'),
                    'application_id' => config('poster.application_id'),
                    'account_name' => config('poster.account_name')
                ]);
            $result = (object)PosterApi::incomingOrders()
                ->createIncomingOrder([
                    'spot_id' => $tablet_id,
                    'phone' => $user->phone,
                    'address' => $order->shipping_address['lines'],
                    'comment' => $this->getComment($order),
                    'products' => $posterProducts,
                    'first_name' => $customer->firstname,
                    'last_name' => $customer->lastname,
                ]);

            if(isset($result->error)) {
                $key = 'layerok.restapi::lang.poster.errors.' . $result->error;
                if(\Lang::has($key)) {
                    $err_text = \Lang::get(
                        'layerok.restapi::lang.poster.errors.' . $result->error
                    );
                } else {
                    $err_text =
                        $result->message;
                }

                throw new ValidationException([
                    $result->error => $err_text
                ]);
            }
        });
    }

    public function getComment(Order $order): string
    {
        $params = [
            'comment' => $order->customer_notes,
            'change' => $order->change,
            'payment_method_name' => $order->payment['method']['name'],
            'delivery_method_name' => $order->shipping['method']['name']
        ];
        $comment = "";

        function is($p, $key)
        {
            if (isset($p[$key]) && !empty($p[$key])) {
                return true;
            }
            return false;
        }

        $sep = " || ";

        if (is($params, 'comment')) {
            $comment .= $params['comment'] . $sep;
        }

        if (is($params, 'change')) {
            $comment .= 'Підготувати решту з' . ": ".$params['change'] . $sep;
        }

        if (is($params, 'payment_method_name')) {
            $comment .=  'Спосіб оплати' . ": " . $params['payment_method_name'] . $sep;
        }

        if (is($params, 'delivery_method_name')) {
            $comment .= 'Спосіб доставки' . ": " . $params['delivery_method_name'] . $sep;
        }
        return substr($comment, 0, -4);
    }

}
