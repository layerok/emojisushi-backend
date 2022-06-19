<?php

namespace Layerok\TgMall\Classes\Utils;

use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Models\State;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\ShippingMethod;

class CheckoutUtils
{

    public static function getClientAddress(State $state)
    {
        return $state->getOrderInfoAddress();
    }

    public static function getComment(State $state)
    {
        return $state->getOrderInfoComment();
    }

    public static function getDeliveryMethodName(State $state)
    {
        $delivery = ShippingMethod::find($state->getOrderInfoDeliveryMethodId());
        return optional($delivery)->name;
    }

    public static function getPaymentMethodName(State $state)
    {
        $payment_method = PaymentMethod::find($state->getOrderInfoPaymentMethodId());

        return optional($payment_method)->name;
    }


    public static function getPhone(Customer $customer)
    {
        return $customer->tg['phone'];
    }

    public static function getProducts(Cart $cart, State $state)
    {
        return $cart->products()->get();
    }

    public static function getFirstName(Customer $customer)
    {
        if (!empty($customer->firstname)) {
            return $customer->firstname;
        }
        return null;
    }

    public static function getLastName(Customer $customer)
    {
        if (!empty($customer->lastname)) {
            return $customer->lastname;
        }

        return null;
    }

    public static function getChange(State $state)
    {
        return $state->getOrderInfoChange();
    }



}
