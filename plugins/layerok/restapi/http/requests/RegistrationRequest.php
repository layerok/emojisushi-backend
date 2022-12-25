<?php
declare(strict_types=1);
namespace Layerok\Restapi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name' => 'required|string',
            'email' => 'unique:users,email|required|string',
            'password' => 'sometimes|confirmed',
            'password_confirmation' => 'required_with:password',
            'surname' => 'required|string',
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
