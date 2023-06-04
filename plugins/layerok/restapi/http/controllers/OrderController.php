<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Layerok\PosterPos\Classes\PosterProducts;
use Layerok\PosterPos\Classes\PosterUtils;
use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\Spot;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Customer\SignUpHandler;
use OFFLINE\Mall\Models\Address;
use Layerok\PosterPos\Models\Order;
use OFFLINE\Mall\Models\PaymentMethod;
use Layerok\PosterPos\Models\ShippingMethod;
use poster\src\PosterApi;
use RainLab\Location\Models\Country;
use Telegram\Bot\Api;
use Layerok\BaseCode\Classes\Receipt;

class OrderController extends Controller
{
    public function place(): JsonResponse
    {
        $data = post();

        $validation = Validator::make($data, [
            'phone'             => 'required|phoneUa',
            'firstname'         => 'min:2|nullable',
            'lastname'          => 'min:2|nullable',
            'email'             => 'email|nullable',
            'shipping_method_id' => 'exists:offline_mall_payment_methods,id',
            'payment_method_id' => 'exists:offline_mall_shipping_methods,id'
        ], [
            'email.required'          => trans('offline.mall::lang.components.signup.errors.email.required'),
            'email.email'             => trans('offline.mall::lang.components.signup.errors.email.email'),
            'phone.phone_ua'          => trans('layerok.posterpos::lang.validation.phone.ua'),
            'email.non_existing_user' => trans('layerok.restapi::validation.customer_exists'),
            'shipping_method_id' => trans('layerok.restapi::validation.shipping_method_exists'),
            'payment_method_id' => trans('layerok.restapi::validation.payment_method_exists'),
        ]);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $shippingMethod = ShippingMethod::where('id', $data['shipping_method_id'])->first();
        $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])->first();

        if($shippingMethod->code === 'courier') {
            $validation = Validator::make($data, [
                'address'           => 'required_if:address_id,null',
                'address_id'        => 'exists:offline_mall_addresses,id|nullable'
            ], [
                'address.required_if' => trans('layerok.restapi::validation.address_required'),
                'address_id.exists' => trans('layerok.restapi::validation.address_id_exists')
            ]);

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
        }

        $jwtGuard = app('JWTGuard');

        $user = $jwtGuard->user();
        $cart = Cart::byUser($user);
        $products = $cart->products()->get();

        if (!count($products) > 0) {
            throw new ValidationException([trans('layerok.restapi::validation.cart_empty')]);
        }

        $spots = Spot::all();
        $spot = Spot::findBySlugOrId(input('spot_slug_or_id'));

        if(!$spot) {
            $spot = $spots->first();
        }

        if(!$user) {
            $user = $this->registerGuest($data);
            $cart->refresh();
        }

        $customer = $user->customer;

        if(!empty($data['address_id'])) {
            $shippingAddress = Address::find($data['address_id']);
        } else {
            $zip = '65125';
            $city = 'Одеса';
            $country_code = 'UA';
            $name = $customer['firstname'] . ' ' . $customer['lastname'];
            // todo: if spot doesn't have address, then you will get validation error
            $lines = $shippingMethod->code === 'courier' ? $data['address']: $spot->address;
            $country_id = Country::where('code', $country_code)->first()->id;

            $shippingAddress = new Address();

            $shippingAddress->name = $name;
            $shippingAddress->lines = $lines;
            $shippingAddress->customer_id = $customer->id;
            $shippingAddress->zip = $zip;
            $shippingAddress->city = $city;
            $shippingAddress->country_id = $country_id;
            $shippingAddress->save();
        }

        $customer->default_billing_address_id = $shippingAddress->id;
        $customer->default_shipping_address_id = $shippingAddress->id;
        $customer->save();
        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($shippingAddress);
        $cart->save();
        $cart->setPaymentMethod($paymentMethod);
        $cart->setShippingMethod($shippingMethod);

        $order = Order::fromCart($cart);
        $order->spot_id = $spot->id;
        $order->save();

        $posterProducts = [];

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

        if (intval($data['sticks']) > 0) {
            $posterProducts[] =   [
                'product_id' => 492,
                'name' => $this->t('sticks_name'),
                'count' => $data['sticks'],
            ];
        }

        //if(env('POSTER_SEND_ORDER_ENABLED')) {
            $tablet_id = $spot->tablet->tablet_id ?? env('POSTER_FALLBACK_TABLET_ID');
        // todo: inline this abstraction
            $poster_comment = PosterUtils::getComment([
                'comment' => $data['comment'] ?? null,
                'change' => $data['change'] ?? null,
                'payment_method_name' => $paymentMethod->name,
                'delivery_method_name' => $shippingMethod->name
            ], function($key) {
                return $this->t($key);
            });

            $config = [
                'access_token' => config('poster.access_token'),
                'application_secret' => config('poster.application_secret'),
                'application_id' => config('poster.application_id'),
                'account_name' => config('poster.account_name')
            ];

            PosterApi::init($config);
            $result = (object)PosterApi::incomingOrders()
                ->createIncomingOrder([
                    'spot_id' => $tablet_id,
                    'phone' => $data['phone'],
                    'address' => $shippingAddress->lines,
                    'comment' => $poster_comment,
                    'products' => $posterProducts,
                    'first_name' => $data['firstname'] ?? "",
                    'last_name' => $data['lastname'] ?? "",
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
        //}

        $token = optional($spot->bot)->token ?? env('TELEGRAM_FALLBACK_BOT_TOKEN');
        $chat_id = optional($spot->chat)->internal_id ?? env('TELEGRAM_FALLBACK_CHAT_ID');
        $api = new Api($token);

        $receipt = $this->getReceipt();
        $receipt
            ->headline($this->t('new_order'))
            ->field('first_name', optional($data)['firstname'])
            ->field('last_name', optional($data)['lastname'])
            ->field('phone', $data['phone'])
            ->field('delivery_method_name', optional($shippingMethod)->name)
            ->field('address', $shippingAddress->lines)
            ->field('payment_method_name', optional($paymentMethod)->name)
            ->field('change', optional($data)['change'])
            ->field('comment', optional($data)['comment'])
            ->newLine()
            ->products($posterProducts)
            ->newLine()
            ->field('total', $cart->getTotalFormattedPrice())
            ->field('spot', $spot->name)
            ->field('target', $receipt->trans('site'));

        $api->sendMessage([
            'text' => $receipt->getText(),
            'parse_mode' => "html",
            'chat_id' => $chat_id
        ]);

        $cart->delete();

        return response()->json([
            'success' => true,
        ]);
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

    public function t($key) {
        return \Lang::get('layerok.tgmall::lang.telegram.receipt.' . $key);
    }


    public function registerGuest($data) {
        $modified_data = $data;

        // mocking some values if they weren't provided in order to be able to create user
        if(empty($data['firstname'])) {
            $modified_data['firstname'] = 'Гість';
        }
        if(empty($data['lastname'])) {
            $modified_data['lastname'] = date('Y-m-d_His');
        }
        if(empty($data['email'])) {
            $modified_data['email'] = 'mall-guest'. date('Y-m-d_His'). '@hmail.com';
        }
        $user = app(SignUpHandler::class)->handle($modified_data, true);
        if ( ! $user) {
            throw new ValidationException(
                [trans('offline.mall::lang.components.quickCheckout.errors.signup_failed')]
            );
        }
        return $user;
    }


}
