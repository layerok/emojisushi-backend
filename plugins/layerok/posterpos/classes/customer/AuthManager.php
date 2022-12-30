<?php

namespace Layerok\PosterPos\Classes\Customer;

use Layerok\PosterPos\Models\User;
use \OFFLINE\Mall\Classes\Customer\AuthManager as AuthManagerBase;

class AuthManager extends AuthManagerBase
{
    protected $userModel = User::class;


}
