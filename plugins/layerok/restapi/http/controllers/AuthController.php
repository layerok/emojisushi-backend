<?php
namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Routing\Controller;
use October\Rain\Exception\ValidationException;
use RainLab\User\Facades\Auth;

class AuthController extends Controller
{
    public function signup() {
        $email = input('email');
        $password = input('password');
        $password_confirmation = input('password_confirmation');
        $activate = input('activate');
        $autoLogin = input('auto_login');
        $name = input('name');
        $agree = input('agree');
        $surname = input('surname');

        if(!$agree) {
            throw new ValidationException(['agree' => \Lang::get('layerok.restapi::validation.checkbox_required')]);
        }

        $user = Auth::findUserByLogin($email);

        if($user) {
            throw new ValidationException(['email' => \Lang::get('layerok.restapi::validation.unique', [
                'attribute' => 'Email'
            ])]);
        }

        Auth::register([
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password_confirmation,
            'name' => $name,
            'surname' => $surname
        ], $activate, $autoLogin);

    }

    public function signin() {
        $login = input('login');
        $password = input('password');

        $user = Auth::attempt([
            'email' => $login,
            'password' => $password
        ]);
    }
}
