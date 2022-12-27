<?php
declare(strict_types=1);

namespace Layerok\Restapi\Http\Controllers;

use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\Wishlist;
use Layerok\Restapi\Http\Requests\RegistrationRequest;

use OFFLINE\Mall\Models\Customer;

/**
 *
 */
class RegistrationController extends Controller
{
    /**
     * @param RegistrationRequest $registrationRequest
     * @throws \ApplicationException
     * @return mixed
     */
    public function __invoke(RegistrationRequest $registrationRequest)
    {
        $user = $this->userPluginResolver
            ->getProvider()
            ->register($registrationRequest->validated());

        if (empty($user)) {
            throw new \ApplicationException('Registration failed');
        }

        $customer            = new Customer();
        $customer->firstname = $user->name;
        $customer->lastname  = $user->surname;
        $customer->user_id   = $user->id;
        $customer->is_guest  = false;
        $customer->save();

        $user->customer = $customer;

        Cart::transferSessionCartToCustomer($user->customer);
        Wishlist::transferToCustomer($user->customer);


        if ($this->userPluginResolver->getResolver()->initActivation($user) !== 'on') {
            return [
                'message' => 'User created'
            ];
        }

        request()->request->add([
            'email' => $user->email,
            'password' => $registrationRequest->password
        ]);

        return app()->call(AuthController::class);
    }
}
