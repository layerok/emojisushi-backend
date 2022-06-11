<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Wishlist;
use OFFLINE\Mall\Models\WishlistItem;
use RainLab\User\Facades\Auth;
use Session;
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

       $wishlists = Wishlist::byUser();

        if ($wishlists->count() < 1) {
            $wishlists = collect([Wishlist::createForUser(Auth::getUser())]);
        }

        $wishlist = $wishlists->first();

        $wishlistItem = WishlistItem::where([
            'product_id' => input('product_id'),
            'wishlist_id' => $wishlist->id,
        ])->first();

        $added = true;

        if($wishlistItem) {
            $wishlistItem->delete();
            $added = false;
        } else {
            WishlistItem::create([
                'product_id' => input('product_id'),
                'quantity' => input('quantity'),
                'wishlist_id' => $wishlist->id,
            ]);
        }

        return response()->json([
            'added' => $added
        ]);

    }
}
