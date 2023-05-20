<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\Spot;
use Layerok\PosterPos\Models\Wishlist;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\WishlistItem;
use Validator;

class WishlistController extends Controller
{
    public function add(): JsonResponse
    {

        $v = Validator::make(get(), [
            'product_id' => 'required|exists:offline_mall_products,id',
            'quantity' => 'nullable|int'
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

       $wishlists = Wishlist::byUser($user);

        if ($wishlists->count() < 1) {
            $wishlists = Wishlist::createForUser($user);
        }

        $wishlist = $wishlists->first();

        $wishlistItem = WishlistItem::where([
            'product_id' => input('product_id'),
            'wishlist_id' => $wishlist->id,
        ])->first();


        if($wishlistItem) {
            $wishlistItem->delete();
        } else {
            WishlistItem::create([
                'product_id' => input('product_id'),
                'quantity' => input('quantity'),
                'wishlist_id' => $wishlist->id,
            ]);
        }

        // todo: optimize that
        $wishlists = Wishlist::byUser($user);

        return response()->json($wishlists->first() ? [
            $wishlists->first()
        ]: []);

    }

    public function list():JsonResponse {
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

        $wishlists = Wishlist::byUser($user);

        return response()->json($wishlists->first()? [
            $wishlists->first()
        ]: []);
    }
}
