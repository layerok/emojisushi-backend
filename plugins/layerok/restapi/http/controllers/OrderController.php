<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Layerok\Basecode\Classes\Receipt;
use Layerok\PosterPos\Classes\ServiceMode;
use Layerok\PosterPos\Classes\ShippingMethodCode;
use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\CartProduct;
use Layerok\PosterPos\Models\Spot;
use Layerok\PosterPos\Models\WayforpaySettings;
use Maksa988\WayForPay\Facades\WayForPay;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Utils\Money;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\PaymentMethod;
use Layerok\PosterPos\Models\ShippingMethod;
use poster\src\PosterApi;
use Telegram\Bot\Api;
use WayForPay\SDK\Domain\Product as WayForPayProduct;
use Maksa988\WayForPay\Collection\ProductCollection;
use Maksa988\WayForPay\Domain\Client;

class OrderController extends Controller
{
    public function place(): JsonResponse
    {
        // to make wayforpay order unique
        $add_to_poster_id = 0;
        $data = post();
        $this->validate($data);

        $jwtGuard = app('JWTGuard');

        $user = $jwtGuard->user();
        $cart = Cart::byUser($user);

        /**
         * @var Spot $spot
         */
        $spot = Spot::find($data['spot_id']);
        $poster_account = $spot->tablet->poster_account;

        if (!$cart->products()->get()->count()) {
            throw new ValidationException([trans('layerok.restapi::validation.cart_empty')]);
        }

        $shippingMethod = ShippingMethod::where('id', $data['shipping_method_id'])->first();
        $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])->first();

        $posterProducts = $cart->products()->get()->map(function (CartProduct $cartProduct) use($poster_account) {
            $item = [];
            $product = $cartProduct->product()->first();
            $item['name'] = $product['name'];
            $item['count'] = $cartProduct['quantity'];

            if($poster_account->account_name === 'emojisushikador' && $product['poster_id2']) {
                $item['product_id'] = $product['poster_id2'];
            } else {
                $item['product_id'] = $product['poster_id'];
            }

            if (isset($cartProduct['variant_id'])) {
                $variant = $cartProduct->getItemDataAttribute();
                $item['modificator_id'] = $variant['poster_id'];
            }
            return $item;
        });

//        $posterProducts = [
//            [
//                'count' => 2,
//                'product_id' => 1
//            ]
//        ];


        PosterApi::init([
            'account_name' => $poster_account->account_name,
            'application_id' => $poster_account->application_id,
            'application_secrete' => $poster_account->application_secret,
            'access_token' => $poster_account->access_token,
        ]);

        $posterComment = collect([
            ['', $data['comment']],
            [\Lang::get('layerok.restapi::lang.receipt.change'), $data['change']],
            [\Lang::get('layerok.restapi::lang.receipt.payment_method'), $paymentMethod->name],
            [\Lang::get('layerok.restapi::lang.receipt.persons_amount'), $data['sticks']],
        ])->filter(fn($part) => !empty($part[1]))
            ->map(fn($part) => ($part[0] ? $part[0] . ': ' : '') . $part[1])
            ->join(' || ');


        $incomingOrder = [
            'spot_id' => $spot->tablet->tablet_id,
            'phone' => $data['phone'],
            'comment' => $posterComment,
            'products' => $posterProducts,
            'first_name' => $data['firstname'] ?? null,
            'last_name' => $data['lastname'] ?? null,
            'service_mode' => ServiceMode::ON_SITE,
        ];

        if ($shippingMethod->code === ShippingMethodCode::COURIER) {
            $incomingOrder['service_mode'] = ServiceMode::COURIER;
            $incomingOrder['address'] = $data['address'] ?? null;
        }

        if ($shippingMethod->code === ShippingMethodCode::TAKEAWAY) {
            $incomingOrder['service_mode'] = ServiceMode::TAKEAWAY;
        }

        // todo: create DTO for the poster order
        $posterResult = (object)PosterApi::incomingOrders()
            ->createIncomingOrder($incomingOrder);

        if (isset($posterResult->error)) {
            $key = 'layerok.restapi::lang.poster.errors.' . $posterResult->error;
            if (\Lang::has($key)) {
                $err_text = \Lang::get(
                    'layerok.restapi::lang.poster.errors.' . $posterResult->error
                );
            } else {
                $err_text =
                    $posterResult->message;
            }

            throw new ValidationException([
                $posterResult->error => $err_text
            ]);
        }

        $poster_order_id = $posterResult->response->incoming_order_id + $add_to_poster_id;

        $receiptProducts = $cart->products()->get()->map(function (CartProduct $cartProduct) {
            $item = [];
            $product = $cartProduct->product()->first();
            $item['name'] = $product['name'];
            $item['count'] = $cartProduct['quantity'];
            return $item;
        });

        $api = new Api($spot->bot->token);

        $money = app()->make(Money::class);
        $receipt = new Receipt();

        $receipt
            ->headline(\Lang::get('layerok.restapi::lang.receipt.new_order') . ' #' . $poster_order_id)
            ->field(\Lang::get('layerok.restapi::lang.receipt.first_name'), $data['firstname'] ?? null)
            ->field(\Lang::get('layerok.restapi::lang.receipt.last_name'), $data['lastname'] ?? null)
            ->field(\Lang::get('layerok.restapi::lang.receipt.phone'), $data['phone'])
            ->field(\Lang::get('layerok.restapi::lang.receipt.delivery_method'), $shippingMethod->name)
            ->field(\Lang::get('layerok.restapi::lang.receipt.address'), $data['address'])
            ->field(\Lang::get('layerok.restapi::lang.receipt.payment_method'), $paymentMethod->name)
            ->field(\Lang::get('layerok.restapi::lang.receipt.change'), $data['change'] ?? null)
            ->field(\Lang::get('layerok.restapi::lang.receipt.persons_amount'), $data['sticks'] ?? null)
            ->field(\Lang::get('layerok.restapi::lang.receipt.comment'), $data['comment'] ?? null)
            ->newLine()
            ->b(\Lang::get('layerok.restapi::lang.receipt.order_items'))
            ->colon()
            ->newLine()
            ->map($receiptProducts, function ($item) {
                $this->product(
                    $item['name'],
                    $item['count']
                )->newLine();
            })
            ->newLine()
            ->field(\Lang::get('layerok.restapi::lang.receipt.total'), $money->format(
                $cart->totals()->totalPostTaxes(),
                null,
                Currency::$defaultCurrency
            ))
            ->field(\Lang::get('layerok.restapi::lang.receipt.target'), 'site');


        $telegramRes = $api->sendMessage([
            'text' => $receipt->getText(),
            'parse_mode' => "html",
            'chat_id' => $spot->chat->internal_id
        ]);

        if ($paymentMethod->code === 'wayforpay') {
            $way_products = $cart->products()->get()->map(function (CartProduct $cartProduct) {
                return new WayForPayProduct(
                    $cartProduct->product->name,
                    ($cartProduct->price()->price / 100),
                    $cartProduct->quantity
                );
            });

            $way_products = new ProductCollection($way_products);
            $client = new Client(
                optional($data)['first_name'],
                optional($data)['last_name'],
                optional($data)['email'],
                optional($data)['phone']
            );
            $total = $cart->totals()->totalPostTaxes() / 100;

            $form = WayForPay::purchase(
                $poster_order_id, $total, $client, $way_products,
                WayforpaySettings::get('currency'),
                null,
                WayforpaySettings::get('language'),
                null,
                $spot->city->thankyou_page_url . "?order_id=$poster_order_id",
                WayforpaySettings::get('service_url') . "?spot_id=$spot->id&telegram_message_id=$telegramRes->messageId",
            )->getAsString(); // Get html form as string

            $cart->delete();
            return response()->json([
                'success' => true,
                'form' => $form,
                'poster_order' => $posterResult->response
            ]);
        }
        $cart->delete();

        return response()->json([
            'success' => true,
            'poster_order' => $posterResult->response
        ]);
    }

    public function validate($data)
    {
        $rules = [
            'phone' => 'required|phoneUa',
            'firstname' => 'min:2|nullable',
            'lastname' => 'min:2|nullable',
            'email' => 'email|nullable',
            'shipping_method_id' => 'exists:offline_mall_shipping_methods,id',
            'payment_method_id' => 'exists:offline_mall_payment_methods,id',
            'spot_id' => 'exists:layerok_posterpos_spots,id'
        ];

        if (isset($data['shipping_method_id'])) {
            $shippingMethod = ShippingMethod::where('id', $data['shipping_method_id'])->first();
            if ($shippingMethod) {
                if ($shippingMethod->code === 'courier') {
                    $rules['address'] = 'required';
                    $messages['address.required'] = trans('layerok.restapi::validation.address_required');
                }
            }
        }

        $messages = [
            'email.required' => trans('offline.mall::lang.components.signup.errors.email.required'),
            'email.email' => trans('offline.mall::lang.components.signup.errors.email.email'),
            'phone.phone_ua' => trans('layerok.posterpos::lang.validation.phone.ua'),
            'email.non_existing_user' => trans('layerok.restapi::validation.customer_exists'),
            'shipping_method_id' => trans('layerok.restapi::validation.shipping_method_exists'),
            'payment_method_id' => trans('layerok.restapi::validation.payment_method_exists'),
            'firstname.min' => trans('layerok.restapi::validation.firstname_min'),
            'lastname.min' => trans('layerok.restapi::validation.lastname_min'),
            'spot_id' => trans('layerok.restapi::validation.spot_exists'),
        ];

        $validation = Validator::make($data, $rules, $messages);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }
    }

}
