<?php
declare(strict_types=1);

namespace Layerok\Restapi\Http\Controllers;


use RainLab\User\Models\User as UserModel;
use Layerok\Restapi\Http\Requests\ActivationRequest;
use Lang;
use Validator;
use ValidationException;
use Auth;

/**
 *
 */
class ResetPasswordController extends Controller
{

    public function __invoke()
    {
        $rules = [
            'code'     => 'required',
            'password' => 'required|between:' . UserModel::getMinPasswordLength() . ',255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $errorFields = ['code' => Lang::get(/*Invalid activation code supplied.*/'rainlab.user::lang.account.invalid_activation_code')];

        /*
         * Break up the code parts
         */
        $parts = explode('!', post('code'));
        if (count($parts) != 2) {
            throw new ValidationException($errorFields);
        }

        list($userId, $code) = $parts;

        if (!strlen(trim($userId)) || !strlen(trim($code)) || !$code) {
            throw new ValidationException($errorFields);
        }

        if (!$user = Auth::findUserById($userId)) {
            throw new ValidationException($errorFields);
        }

        if (!$user->attemptResetPassword($code, post('password'))) {
            throw new ValidationException($errorFields);
        }

        // Check needed for compatibility with legacy systems
        if (method_exists(\RainLab\User\Classes\AuthManager::class, 'clearThrottleForUserId')) {
            Auth::clearThrottleForUserId($user->id);
        }
    }
}
