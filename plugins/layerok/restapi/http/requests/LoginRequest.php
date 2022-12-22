<?php
declare(strict_types=1);
namespace Layerok\Restapi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 *
 */
class LoginRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function messages()
    {
       return parent::messages();
    }
}
