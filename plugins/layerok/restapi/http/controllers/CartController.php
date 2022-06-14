<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Exceptions\OutOfStockException;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\Product;

class CartController extends Controller
{

    public function all(): JsonResponse
    {
        $cart = Cart::bySession();
        $records = $this->prepareProducts($cart);

        return response()->json([
            'data' => $records,
            'total' => $cart->getTotalFormattedPrice(),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function add(): JsonResponse
    {
        $product_id = input('product_id');
        $quantity = input('quantity');

        request()->validate([
            'product_id' => 'required|exists:offline_mall_products,id'
        ]);

        $product = Product::published()->findOrFail($product_id);

        $cart = Cart::bySession();

        $cartProduct = CartProduct::where([
            ['cart_id', $cart->id],
            ['product_id', $product_id]
        ])->first();

        try {
            if($cartProduct && $cartProduct->quantity + (int)$quantity <= 0) {
                $cart->removeProduct($cartProduct);
            } else {
                $cart->addProduct($product, $quantity);
            }

        } catch (OutOfStockException $e) {
            throw new ValidationException(['quantity' => trans('offline.mall::lang.common.stock_limit_reached')]);
        }

        $cart->refresh();

        $records = $this->prepareProducts($cart);


        return response()->json([
            'data' => $records,
            'total' => $cart->getTotalFormattedPrice(),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function remove() {
        $cart_product_id = input('cart_product_id');

        request()->validate([
            'cart_product_id' => 'required|exists:offline_mall_cart_products,id'
        ]);

        $cart = Cart::bySession();

        $cartProduct = CartProduct::where([
            ['cart_id', $cart->id],
            ['id', $cart_product_id]
        ])->first();

        $cart->removeProduct($cartProduct);

        $cart->refresh();

        $records = $this->prepareProducts($cart);


        return response()->json([
            'data' => $records,
            'total' => $cart->getTotalFormattedPrice(),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function clear(): JsonResponse {
        $cart = Cart::bySession();
        $cart->products()->delete();
        $cart->refresh();

        return response()->json([
            'data' => $cart->products()->get()->toArray(),
            'total' => $cart->getTotalFormattedPrice(),
            'totalQuantity' => $cart->getTotalQuantity()
        ]);
    }

    public function prepareProducts($cart) {
        return $cart->products()->with([
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
