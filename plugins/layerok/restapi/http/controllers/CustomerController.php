<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\User;

class CustomerController extends Controller
{

    public function save()
    {
        $firstname = input('firstname');
        $lastname = input('lastname');
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

        $customer = $user->customer;

        if($firstname) {
            $customer->firstname = $firstname;
        }

        if($lastname) {
            $customer->lastname = $lastname;
        }

        $customer->save();
    }
}
