<?php
declare(strict_types=1);
namespace Layerok\Restapi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OFFLINE\Mall\Models\User;

/**
 *
 */
class RegistrationRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        $minPasswordLength = User::getMinPasswordLength();
        return [
            'name' => 'required|string|min:2',
            'email' => 'unique:users,email|required|string',
            'password' => "required|between:$minPasswordLength,255|confirmed",
            'password_confirmation' => "required_with:password|between:$minPasswordLength,255",
            'surname' => 'required|string|min:2',
            'agree' => 'accepted'
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => \Lang::get('layerok.restapi::validation.unique', [
                'attribute' => 'Email'
            ]),
            'agree.accepted' => \Lang::get('layerok.restapi::validation.checkbox_required')
        ];
    }
}
