<?php

namespace Layerok\RestApi\Classes\Customer;

use DB;
use Event;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Classes\Customer\SignUpHandler;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\User;
use OFFLINE\Mall\Models\Wishlist;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\UserGroup;
use System\Classes\PluginManager;

class DefaultSignUpHandler implements SignUpHandler
{
    protected $asGuest;

    public function handle(array $data, bool $asGuest = false): ?User
    {
        $this->asGuest = $asGuest;

        return $this->signUp($data);
    }

    /**
     * @throws ValidationException
     */
    protected function signUp(array $data): ?User
    {
        if ($this->asGuest) {
            $data['password'] = $data['password_repeat'] = str_random(30);
        }

        $this->validate($data);

        $requiresConfirmation = ($data['requires_confirmation'] ?? false);

        Event::fire('mall.customer.beforeSignup', [$this, $data]);

        $user = DB::transaction(function () use ($data, $requiresConfirmation) {

            $user = $this->createUser($data, $requiresConfirmation);

            $customer            = new Customer();
            $customer->firstname = $data['firstname'];
            $customer->lastname  = $data['lastname'];
            $customer->user_id   = $user->id;
            $customer->is_guest  = $this->asGuest;
            $customer->save();

            Cart::transferSessionCartToCustomer($user->customer);
            Wishlist::transferToCustomer($user->customer);

            return $user;
        });

        // To prevent multiple guest accounts with the same email address we edit
        // the email of all existing guest accounts registered to the same email.
        $this->renameExistingGuestAccounts($data, $user);

        Event::fire('mall.customer.afterSignup', [$this, $user]);

        if ($requiresConfirmation === true) {
            return $user;
        }

        $credentials = [
            'login'    => array_get($data, 'email'),
            'password' => array_get($data, 'password'),
        ];

        return Auth::authenticate($credentials, true);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $data)
    {
        $rules = self::rules();

        if ($this->asGuest) {
            unset($rules['password'], $rules['password_repeat']);
        }

        $messages = self::messages();

        $validation = Validator::make($data, $rules, $messages);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }
    }

    protected function createUser($data, $requiresConfirmation): User
    {
        $data['name']                  = $data['firstname'];
        $data['surname']               = $data['lastname'];
        $data['email']                 = $data['email'];
        $data['password']              = $data['password'];
        $data['password_confirmation'] = $data['password_repeat'];

        $user = Auth::register($data, ! $requiresConfirmation);
        if ($this->asGuest && $user && $group = UserGroup::getGuestGroup()) {
            $user->groups()->sync($group);
        } else {
            $user->groups()->sync([]);
        }

        return $user;
    }


    protected function renameExistingGuestAccounts(array $data, $user)
    {
        // Add a "mall-guest_2021-05-31_075100" suffix to the already registered email.
        $parts = explode('@', $data['email']);
        $suffix = 'mall-guest_' . date('Y-m-d_His');

        $newEmail = sprintf('%s+%s@%s', $parts[0], $suffix, $parts[1]);

        User::where('id', '<>', $user->id)
            ->where('email', $data['email'])
            ->whereHas('customer', function ($q) {
                $q->where('is_guest', 1);
            })
            ->update(['email' => $newEmail, 'username' => $newEmail]);
    }

    public static function rules($forSignup = true): array
    {
        $minPasswordLength = \RainLab\User\Models\User::getMinPasswordLength();
        $rules = [
            'firstname'           => 'required',
            'lastname'            => 'required',
            'email'               => ['required', 'email', ($forSignup ? 'non_existing_user' : null)],
            'password'            => sprintf('required|min:%d|max:255', $minPasswordLength),
            'password_repeat'     => 'required|same:password',
        ];


        Event::fire('mall.customer.extendSignupRules', [&$rules, $forSignup]);

        if (PluginManager::instance()->hasPlugin('Winter.Location')) {
            $translatedRules = array_where($rules, function ($value, $key) {
                return (is_string($value) && str_contains($value, 'rainlab_'));
            });
            foreach (array_keys($translatedRules) as $rule) {
                $rules[$rule] = str_replace('rainlab_', 'winter_', $rules[$rule]);
            }
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'email.required'          => trans('offline.mall::lang.components.signup.errors.email.required'),
            'email.email'             => trans('offline.mall::lang.components.signup.errors.email.email'),
            'email.unique'            => trans('offline.mall::lang.components.signup.errors.email.unique'),
            'email.non_existing_user' => trans('offline.mall::lang.components.signup.errors.email.non_existing_user'),

            'firstname.required'           => trans('offline.mall::lang.components.signup.errors.firstname.required'),
            'lastname.required'            => trans('offline.mall::lang.components.signup.errors.lastname.required'),

            'password.required' => trans('offline.mall::lang.components.signup.errors.password.required'),
            'password.min'      => trans('offline.mall::lang.components.signup.errors.password.min'),
            'password.max'      => trans('offline.mall::lang.components.signup.errors.password.max'),

            'password_repeat.required' => trans('offline.mall::lang.components.signup.errors.password_repeat.required'),
            'password_repeat.same'     => trans('offline.mall::lang.components.signup.errors.password_repeat.same'),

        ];
    }
}
