<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Layerok\PosterPos\Classes\PosterProducts;
use Layerok\PosterPos\Classes\PosterUtils;
use Layerok\PosterPos\Models\Spot;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\ShippingMethod;
use Telegram\Bot\Api;
use Layerok\BaseCode\Classes\Receipt;

class OrderController extends Controller
{
    public $cart = null;
    public function place(): JsonResponse
    {

        $data = post();

        $rules = [
            'phone'             => 'required|phoneUa',
            'email'             => 'email|nullable',
        ];

        $messages = [
            'email.required'          => trans('offline.mall::lang.components.signup.errors.email.required'),
            'email.email'             => trans('offline.mall::lang.components.signup.errors.email.email'),
            'phone.phone_ua'          =>  "Не верный формат украинского номера"
        ];

        $validation = Validator::make($data, $rules, $messages);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $this->cart = Cart::bySession();

        $products = $this->cart->products()->get();

        if (!count($products) > 0) {
            throw new ValidationException(['Ваш заказ пустой. Пожалуйста добавьте товар в корзину.']);
        }

        $spot = $this->getSelectedSpot();

        $posterProducts = new PosterProducts();

        $posterProducts
            ->addCartProducts($products)
            ->addSticks(
                $data['sticks']
            );

        $shipping_id = $data['shipping_id'];
        $payment_id = $data['payment_id'];

        $shipping = ShippingMethod::where('id', $shipping_id)->first();
        $payment = PaymentMethod::where('id', $payment_id)->first();

        $poster_comment = PosterUtils::getComment([
            'comment' => $data['comment'] ?? null,
            'change' => $data['change'] ?? null,
            'payment_method_name' => $payment->name,
            'delivery_method_name' => $shipping->name
        ]);

        $token = optional($spot->bot)->token ?? env('TELEGRAM_FALLBACK_BOT_TOKEN');
        $chat_id = optional($spot->chat)->internal_id ?? env('TELEGRAM_FALLBACK_CHAT_ID');
        $api = new Api($token);

        $receipt = $this->getReceipt();
        $receipt
            ->headline("Новый заказ!")
            ->field('first_name', $data['name'])
            ->field('last_name', optional($data)['last_name'])
            ->field('phone', $data['phone'])
            ->field('comment', $poster_comment)
            ->field('delivery_method_name', $shipping->name)
            ->field('payment_method_name', $payment->name)
            ->newLine()
            ->products($products)
            ->newLine()
            ->field('total', $this->cart->getTotalFormattedPrice());

        $api->sendMessage([
            'text' => $receipt->getText(),
            'parse_mode' => "html",
            'chat_id' => $chat_id
        ]);


        return response()->json([

        ]);
    }

    public function getSelectedSpot() {
        $spots = Spot::all();


        $spot_id = input('spot_id');

        foreach ($spots as $spot) {
            if ($spot_id == $spot['id']) {
                return $spot;
            }
        }

        // По умолчанию будет выбрано первое заведение
        $selectedSpot = $spots->first();
    }

    public function getReceipt(): Receipt
    {
        $receipt = new Receipt();

        $receipt->setProductNameResolver(function(CartProduct $cartProduct) {
            return $cartProduct->product->name;
        });
        $receipt->setProductCountResolver(function(CartProduct $cartProduct) {
            return $cartProduct->quantity;
        });

        $receipt->setTransResolver(function($key) {
            return \Lang::get('layerok.tgmall::lang.telegram.receipt.' . $key);
        });

        return $receipt;
    }


}
