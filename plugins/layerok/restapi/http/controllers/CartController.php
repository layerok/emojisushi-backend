<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Exceptions\OutOfStockException;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\Product;
use Session;

class CartController extends Controller
{

    public function all(): JsonResponse
    {
        $cart = Cart::bySession();
        $records = $this->prepareProducts($cart);

        return response()->json([
            'data' => $records,
            'meta' => [
                'total' => $cart->getTotalFormattedPrice(),
                'totalQuantity' => $cart->getTotalQuantity()
            ]
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
            'meta' => [
                'total' => $cart->getTotalFormattedPrice(),
                'totalQuantity' => $cart->getTotalQuantity()
            ]
        ]);
    }

    public function prepareProducts($cart) {
        return $cart->products()->get()->each(function($p) {
            $p->hidden = [];
            $p->price_formatted = $p->product->price()->price_formatted;
        });
    }


}
