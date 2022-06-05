<?php

namespace Layerok\TgMall\Features\Checkout;

use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Receipt;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Utils\CheckoutUtils;
use OFFLINE\Mall\Models\CartProduct;


class PreConfirmOrderHandler extends Handler
{
    use Lang;

    protected $name = "pre_confirm_order";

    public function run()
    {
        $products = CheckoutUtils::getProducts($this->getCart(), $this->getState());
        $phone = CheckoutUtils::getPhone($this->getCustomer());
        $firstName = CheckoutUtils::getFirstName($this->getCustomer());
        $lastName = CheckoutUtils::getLastName($this->getCustomer());

        $receipt = $this->getReceipt();

        $receipt
            ->headline("Подтверждаете заказ?")
            ->field('first_name', $firstName)
            ->field('last_name', $lastName)
            ->field('phone', $phone)
            ->field('comment', $this->getState()->getOrderInfoComment())
            ->field('delivery_method_name', CheckoutUtils::getDeliveryMethodName($this->getState()))
            ->field('payment_method_name', CheckoutUtils::getPaymentMethodName($this->getState()))
            ->newLine()
            ->products($products)
            ->newLine()
            ->field('total', $this->getCart()->getTotalFormattedPrice());

        $k = new ConfirmOrderKeyboard();

        $this->sendMessage([
            'text' => $receipt->getText(),
            'parse_mode' => 'html',
            'reply_markup' => $k->getKeyboard()
        ]);
        $this->getState()->setMessageHandler(null);
    }
}
