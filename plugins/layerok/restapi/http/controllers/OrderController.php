<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Layerok\PosterPos\Classes\PosterProducts;
use Layerok\PosterPos\Classes\PosterUtils;
use Layerok\PosterPos\Models\Spot;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\ShippingMethod;
use poster\src\PosterApi;
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
            'phone.phone_ua'          => trans('layerok.posterpos::lang.validation.phone.ua')
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
            ->addProduct(
                492,
                $this->t('sticks_name'),
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
        ], function($key) {
            return $this->t($key);
        });


        $tablet_id = $spot->tablet->tablet_id ?? env('POSTER_FALLBACK_TABLET_ID');

        if(env('POSTER_SEND_ORDER_ENABLED')) {
            PosterApi::init();
            $result = (object)PosterApi::incomingOrders()
                ->createIncomingOrder([
                    'spot_id' => $tablet_id,
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? "",
                    'comment' => $poster_comment,
                    'products' => $posterProducts->all(),
                    'first_name' => $data['first_name'] ?? "",
                ]);

            if(isset($result->error)) {
                $poster_err = \Lang::get(
                    'layerok.restapi::lang.poster.errors.' . $result->error,
                    [],
                    null,
                    $result->message
                );
                throw new ValidationException([
                    $result->error => $poster_err
                ]);
            }
        }

        $token = optional($spot->bot)->token ?? env('TELEGRAM_FALLBACK_BOT_TOKEN');
        $chat_id = optional($spot->chat)->internal_id ?? env('TELEGRAM_FALLBACK_CHAT_ID');
        $api = new Api($token);

        $receipt = $this->getReceipt();
        $receipt
            ->headline($this->t('new_order'))
            ->field('first_name', optional($data)['first_name'])
            ->field('last_name', optional($data)['last_name'])
            ->field('phone', $data['phone'])
            ->field('delivery_method_name', optional($shipping)->name)
            ->field('address', optional($data)['address'])
            ->field('payment_method_name', optional($payment)->name)
            ->field('change', optional($data)['change'])
            ->field('comment', optional($data)['comment'])
            ->newLine()
            ->products($posterProducts->all())
            ->newLine()
            ->field('total', $this->cart->getTotalFormattedPrice())
            ->field('spot', $spot->name)
            ->field('target', $receipt->trans('site'));

        $api->sendMessage([
            'text' => $receipt->getText(),
            'parse_mode' => "html",
            'chat_id' => $chat_id
        ]);


        $this->cart->delete();



        return response()->json([
            'success' => true,
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
        return $spots->first();
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


}
