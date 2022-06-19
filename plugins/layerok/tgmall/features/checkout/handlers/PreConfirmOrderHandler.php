<?php

namespace Layerok\TgMall\Features\Checkout\Handlers;

use Event;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Keyboards\YesNoKeyboard;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Utils\CheckoutUtils;


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
            ->headline(self::lang('receipt.confirm_order_question'))
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

        if($result = Event::fire('tgmall.order.preconfirm.receipt', [$this], true)) {
            $receipt = $result;
        }

        $k = new YesNoKeyboard([
            'yes' => [
                'handler' => 'confirm_order'
            ],
            'no' => [
                'handler' => 'start'
            ]
        ]);

        $this->sendMessage([
            'text' => $receipt->getText(),
            'parse_mode' => 'html',
            'reply_markup' => $k->getKeyboard()
        ]);
        $this->getState()->setMessageHandler(null);
    }
}
