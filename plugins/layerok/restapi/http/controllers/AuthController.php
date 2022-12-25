<?php
declare(strict_types=1);

namespace Layerok\Restapi\Http\Controllers;

use October\Rain\Argon\Argon;
use OFFLINE\Mall\Models\Customer;
use ReaZzon\JWTAuth\Classes\Dto\TokenDto;
use Layerok\Restapi\Http\Requests\LoginRequest;

/**
 *
 */
class AuthController extends Controller
{
    /**
     * @param LoginRequest $loginRequest
     * @return array
     * @throws \ApplicationException
     */
    public function __invoke(LoginRequest $loginRequest): array
    {
        $user = $this->userPluginResolver
            ->getProvider()
            ->authenticate($loginRequest->validated());

        if (empty($user)) {
            throw new \ApplicationException('Login failed');
        }

        // If the user doesn't have a Customer model it was created via the backend.
        // Make sure to add the Customer model now
        if ( ! $user->customer && ! $user->is_guest) {
            $customer            = new Customer();
            $customer->firstname = $user->name;
            $customer->lastname  = $user->surname;
            $customer->user_id   = $user->id;
            $customer->is_guest  = false;
            $customer->save();

            $user->customer = $customer;
        }

        $tokenDto = new TokenDto([
            'token' => $this->JWTGuard->login($user),
            'expires' => Argon::createFromTimestamp($this->JWTGuard->getPayload()->get('exp')),
            'user' => $user->load('customer'),
        ]);

        return ['data' => $tokenDto->toArray()];
    }
}
