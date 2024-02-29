<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\Address;
use OFFLINE\Mall\Models\User;
use RainLab\Location\Models\Country;

class UserController extends Controller
{
    public function fetch(): JsonResponse
    {
        $jwtGuard = app('JWTGuard');
        $user = User::with([
            'customer.addresses',
            'customer.orders.products.product.image_sets',
            'customer.orders.order_state'
        ])
            ->find($jwtGuard->user()->id);
        return response()->json($user);
    }

    public function save()
    {
        $name = input('name');
        $surname = input('surname');
        $phone = input('phone');

        request()->validate([
            'name' => 'required|min:2',
            'surname' => 'required|min:2',
            'phone' => 'nullable|phoneUa'
        ], [
            'phone.phone_ua' => trans('layerok.posterpos::lang.validation.phone.ua')
        ]);

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $customer = $user->customer;

        if($name) {
            $user->name = $name;
            $customer->firstname = $name;
        }

        if($surname) {
            $user->surname = $surname;
            $customer->lastname = $surname;
        }

        if($phone) {
            $user->phone = $phone;
        }

        $customer->save();
        $user->save();
        return response()->json($user);
    }

    public function updatePassword() {


        $minPasswordLength = User::getMinPasswordLength();

        request()->validate([
           'password_old' => "required",
           'password' => "required|between:$minPasswordLength,255|confirmed",
           'password_confirmation' => "required_with:password|between:$minPasswordLength,255"
        ]);


        $password_old = input('password_old');
        $password = input('password');
        $password_confirmation = input('password_confirmation');

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

        if($user->checkHashValue('password', $password_old)) {
            $user->password = $password;
            $user->password_confirmation = $password_confirmation;
            $user->save();
        } else {
            throw new \ValidationException(
                ['password_old' => \Lang::get('layerok.restapi::validation.not_correct_password')]
            );
        }


    }

    public function createAddress() {
        request()->validate([
            'name' => 'required',
            'lines' => 'required',
            'zip' => 'required',
            'city' => 'required',
            'two_letters_country_code' => 'required'
        ]);

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $customer = $user->customer;

        $name = input('name');
        $lines = input('lines');
        $zip = input('zip');
        $city = input('city');
        $two_letters_country_code =  input('two_letters_country_code');

        $shippingAddress             = new Address();
        $shippingAddress->name       = $name;
        $shippingAddress->lines      = $lines;
        $shippingAddress->zip        = $zip;
        $shippingAddress->city       = $city;

        $country = Country::where('code', 'UA')->first();
        if($country) {
            $shippingAddress->country_id = $country->id;
        } else {
            throw new \ValidationException([
                'two_letter_country_code' => "Country with code[$two_letters_country_code] doesn't exist"
            ]);
        }


        $customer->addresses()->save($shippingAddress);
        return response()->json($shippingAddress);
    }

    public function deleteAddress() {
        request()->validate([
            'id' => 'required|integer|exists:offline_mall_addresses'
        ]);

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $customer = $user->customer;

        $id = input('id');

        $address = Address::where([
            ['id', $id],
            ['customer_id', $customer->id]
        ])->first();
        if($address) {
            $address->delete();
        }

    }

    public function setDefaultAddress() {
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $customer = $user->customer;

        request()->validate([
            'id' => 'required|integer|exists:offline_mall_addresses'
        ]);

        $id = input('id');

        $address = Address::where([
            ['id', $id],
            ['customer_id', $customer->id]
        ])->first();

        if($address) {
            $customer->default_shipping_address_id = $address->id;
            $customer->save();
        }
    }
}
