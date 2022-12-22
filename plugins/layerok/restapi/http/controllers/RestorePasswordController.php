<?php
declare(strict_types=1);

namespace Layerok\Restapi\Http\Controllers;

use Cms\Classes\Page;
use RainLab\User\Models\User as UserModel;
use Lang;
use Mail;
use Validator;
use ValidationException;
use ApplicationException;

/**
 *
 */
class RestorePasswordController extends Controller
{
    public function __invoke()
    {
        $redirect_url = input('redirect_url') ?? Page::url('reset.htm');

        $rules = [
            'email' => 'required|email|between:6,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = UserModel::findByEmail(post('email'));
        if (!$user || $user->is_guest) {
            throw new ApplicationException(Lang::get(/*A user was not found with the given credentials.*/'rainlab.user::lang.account.invalid_user'));
        }

        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);

        $link = $redirect_url . '/' . $code;

        $data = [
            'name' => $user->name,
            'username' => $user->username,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.restore', $data, function($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });
    }

}
