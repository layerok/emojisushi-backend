<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Cart;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Exceptions\OutOfStockException;

use Layerok\PosterPos\Models\CartProduct;
use OFFLINE\Mall\Classes\Utils\Money;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;

class CartController extends Controller
{

    public function all(): JsonResponse
    {
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $cart = Cart::byUser($user);
        $records = $this->prepareProducts($cart);
        $money = app()->make(Money::class);

        return response()->json([
            'data' => $records,
            'total' => $money->format(
                $cart->totals()->totalPostTaxes(),
                null,
                Currency::$defaultCurrency
            ),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function add(): JsonResponse
    {
        request()->validate([
            'product_id' => 'required|exists:offline_mall_products,id'
        ]);

        $product_id = input('product_id');
        $variant_id = input('variant_id');
        $quantity = input('quantity');

        $product = Product::published()->findOrFail($product_id);

        $variant = Variant::where('id', $variant_id)->first();

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $cart = Cart::byUser($user);

        $cartProduct = CartProduct::where([
            ['cart_id', $cart->id],
            ['product_id', $product->id],
            ['variant_id', $variant->id ?? null]
        ])->first();

        try {
            if($cartProduct && $cartProduct->quantity + (int)$quantity <= 0) {
                $cart->removeProduct($cartProduct);
            } else {
                $cart->addProduct($product, $quantity, $variant);
            }

        } catch (OutOfStockException $e) {
            throw new ValidationException(['quantity' => trans('offline.mall::lang.common.stock_limit_reached')]);
        }

        $cart->refresh();

        $records = $this->prepareProducts($cart);

        $money = app()->make(Money::class);

        return response()->json([
            'data' => $records,
            'total' => $money->format(
                $cart->totals()->totalPostTaxes(),
                null,
                Currency::$defaultCurrency
            ),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function remove() {

        request()->validate([
            'cart_product_id' => 'required|exists:offline_mall_cart_products,id'
        ]);

        $cart_product_id = input('cart_product_id');
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

        $cart = Cart::byUser($user);

        $cartProduct = CartProduct::where([
            ['cart_id', $cart->id],
            ['id', $cart_product_id]
        ])->first();

        $cart->removeProduct($cartProduct);

        $cart->refresh();

        $records = $this->prepareProducts($cart);
        $money = app()->make(Money::class);

        return response()->json([
            'data' => $records,
            'total' => $money->format(
                $cart->totals()->totalPostTaxes(),
                null,
                Currency::$defaultCurrency
            ),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function clear(): JsonResponse {
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();
        $cart = Cart::byUser($user);
        $cart->products()->delete();
        $cart->refresh();
        $money = app()->make(Money::class);


        return response()->json([
            'data' => $cart->products()->get()->toArray(),
            'total' => $money->format(
                $cart->totals()->totalPostTaxes(),
                null,
                Currency::$defaultCurrency
            ),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function prepareProducts($cart) {
        return $cart->products()->with([
            'variant',
            'variant.property_values',
            'variant.additional_prices',
            'variant.prices',
            'product.image_sets',
            'product.prices',
            'product.additional_prices',
            'product.property_values' => function($query) {
                $query->where('value', '1');
            },
        ])->get()->each(function($p) {
            $p->hidden = [];
            $p->price_formatted = $p->product->price()->price_formatted;
        });
    }


}
