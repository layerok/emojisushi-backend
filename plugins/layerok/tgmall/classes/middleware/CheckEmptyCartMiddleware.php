<?php

namespace Layerok\TgMall\Classes\Middleware;

use OFFLINE\Mall\Models\Cart;

class CheckEmptyCartMiddleware extends AbstractMiddleware
{
    public function run():bool
    {
        if (!isset($this->customer)) {
            return false;
        }

        $cart = Cart::byUser($this->customer->user);

        if (!isset($cart)) {
            return false;
        }

        $this->products = $cart->products()->get();

        if (!count($this->products) > 0) {
            return false;
        }
        return true;
    }

    public function onFailed():void
    {
        $this->telegram->sendMessage([
            'text' => 'Ваша корзина пуста. Пожалуйста добавьте товар в корзину.',
            'chat_id' => $this->update->getChat()->id,
            'reply_markup' => CartEmptyReplyMarkup::getKeyboard()
        ]);
    }
}
