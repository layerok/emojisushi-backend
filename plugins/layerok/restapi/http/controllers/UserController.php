<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OFFLINE\Mall\Models\User;

class UserController extends Controller
{
    public function fetch(): JsonResponse
    {
        $jwtGuard = app('JWTGuard');
        return response()->json(User::with('customer')->find($jwtGuard->user()->id));
    }

    public function save()
    {
        $name = input('name');
        $surname = input('surname');
        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

        if($name) {
            $user->name = $name;
        }

        if($surname) {
            $user->surname = $surname;
        }

        $user->save();
    }

    public function updatePassword() {


        $minPasswordLength = User::getMinPasswordLength();

        request()->validate([
           'password_old' => "required",
           'password' => "required|between:$minPasswordLength,255|confirmed",
           'password_confirmation' => "required_with:password|between:$minPasswordLength,255"
        ]);


        $password_old = input('password_old');
        $password = input('password');
        $password_confirmation = input('password_confirmation');

        $jwtGuard = app('JWTGuard');
        $user = $jwtGuard->user();

        if($user->checkHashValue('password', $password_old)) {
            $user->password = $password;
            $user->password_confirmation = $password_confirmation;
            $user->save();
        } else {
            throw new \ValidationException(
                ['password_old' => \Lang::get('layerok.restapi::validation.not_correct_password')]
            );
        }


    }
}
