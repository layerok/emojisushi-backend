<?php

namespace Layerok\Restapi\Http\Middleware;

use Closure;
use Config;
use Illuminate\Http\Request;
use Session;

class CustomSession
{
    public function handle(Request $request, Closure $next)
    {
        $session_id = input('session_id');
        $spot_id = input('spot_id');

        session()->setId($session_id);
        Session::put('cart_session_id', $session_id);
        Session::put('wishlist_session_id', $session_id);
        Session::put('spot_id', $spot_id);


        $lang = input('lang');
        if($lang) {
            app()->setLocale($lang);
        }

        return $next($request);
    }

}
