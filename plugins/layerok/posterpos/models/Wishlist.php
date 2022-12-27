<?php
namespace Layerok\PosterPos\Models;


use Cookie;
use Illuminate\Support\Collection;

use \OFFLINE\Mall\Models\Wishlist as WishlistBase;

use RainLab\User\Models\User;
use Session;

class Wishlist extends WishlistBase {

    /**
     * Return all wishlists for the currently logged in user or
     * the currently active user session.
     */
    public static function byUser(?User $user = null): Collection
    {
        $sessionId = static::getSessionId();
        $spot_id = Session::get('spot_id');

        return self::where([
            ['session_id', $sessionId],
            ['spot_id', $spot_id]
        ])
            ->when($user && $user->customer, function ($q) use ($user, $spot_id) {
                $q->orWhere([
                    ['customer_id', $user->customer->id],
                    ['spot_id', $spot_id]
                ]);
            })
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Generate a unique wishlist session id.
     *
     * @return string
     */
    public static function getSessionId(): string
    {
        $sessionId = Session::get('wishlist_session_id') ?? Cookie::get('wishlist_session_id') ?? str_random(100);
        Cookie::queue('wishlist_session_id', $sessionId, 9e6);
        Session::put('wishlist_session_id', $sessionId);

        return $sessionId;
    }

    /**
     * Create a new wishlist for a specified user or the currently active session.
     */
    public static function createForUser(?User $user, string $name = null): self
    {
        $spot_id = Session::get('spot_id');
        $attributes = $user && $user->customer
            ? ['customer_id' => $user->customer->id]
            : ['session_id' => static::getSessionId()];

        $name = $name ?? trans('offline.mall::frontend.wishlist.default_name');

        return Wishlist::create(array_merge($attributes, [
            'name' => $name,
            'spot_id' => $spot_id
        ]));
    }

}
