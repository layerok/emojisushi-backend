<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\Spot;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Customer\SignUpHandler;
use OFFLINE\Mall\Models\Address;
use Layerok\PosterPos\Models\Order;
use OFFLINE\Mall\Models\PaymentMethod;
use Layerok\PosterPos\Models\ShippingMethod;
use OFFLINE\Mall\Models\Product;
use RainLab\Location\Models\Country;

class OrderController extends Controller
{
    public function place(): JsonResponse
    {
        $data = post();
        $this->validate($data);

        $jwtGuard = app('JWTGuard');

        $user = $jwtGuard->user();
        $cart = Cart::byUser($user);
        $spot = Spot::findBySlugOrId(input('spot_slug_or_id'));

        if(!$spot) {
            $spots = Spot::all();
            $spot = $spots->first();
        }

        if(!$user) {
            $user = $this->registerGuest($data);
            $cart->refresh();
        }

        $shippingMethod = ShippingMethod::where('id', $data['shipping_method_id'])->first();
        $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])->first();

        if($shippingMethod->code === 'courier') {
            if(!empty($data['address_id'])) {
                $shippingAddress = Address::find($data['address_id']);
            } else {
                $zip = '65125';
                $city = 'Одеса';
                $country_code = 'UA';
                $name = $user->customer['firstname'] . ' ' . $user->customer['lastname'];
                $lines = $data['address'];
                $country_id = Country::where('code', $country_code)->first()->id;

                $shippingAddress = new Address();

                $shippingAddress->name = $name;
                $shippingAddress->lines = $lines;
                $shippingAddress->customer_id = $user->customer->id;
                $shippingAddress->zip = $zip;
                $shippingAddress->city = $city;
                $shippingAddress->country_id = $country_id;
                $shippingAddress->save();
            }
        } else {
            $shippingAddress = $spot->address;
        }

        if (intval($data['sticks']) > 0) {
            $sticks = Product::where('poster_id', 492)->first();
            $cart->addProduct($sticks, $data['sticks']);
        }

        $user->customer->default_billing_address_id = $shippingAddress->id;
        $user->customer->default_shipping_address_id = $shippingAddress->id;
        $user->customer->save();
        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($shippingAddress);
        $cart->save();
        $cart->setPaymentMethod($paymentMethod);
        $cart->setShippingMethod($shippingMethod);

        $order = Order::fromCart($cart);
        $order->spot_id = $spot->id;
        $order->customer_notes = $data['comment'];
        $order->change = $data['change'];
        $order->save();

        \Event::fire('restapi::order.created', [$order, $cart, $spot]);

        $cart->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function validate($data) {
        $validation = Validator::make($data, [
            'phone'             => 'required|phoneUa',
            'firstname'         => 'min:2|nullable',
            'lastname'          => 'min:2|nullable',
            'email'             => 'email|nullable',
            'shipping_method_id' => 'exists:offline_mall_shipping_methods,id',
            'payment_method_id' => 'exists:offline_mall_payment_methods,id'
        ], [
            'email.required'          => trans('offline.mall::lang.components.signup.errors.email.required'),
            'email.email'             => trans('offline.mall::lang.components.signup.errors.email.email'),
            'phone.phone_ua'          => trans('layerok.posterpos::lang.validation.phone.ua'),
            'email.non_existing_user' => trans('layerok.restapi::validation.customer_exists'),
            'shipping_method_id' => trans('layerok.restapi::validation.shipping_method_exists'),
            'payment_method_id' => trans('layerok.restapi::validation.payment_method_exists'),
            'firstname.min' => trans('layerok.restapi::validation.firstname_min'),
            'lastname.min' => trans('layerok.restapi::validation.lastname_min'),
        ]);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $shippingMethod = ShippingMethod::where('id', $data['shipping_method_id'])->first();

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
