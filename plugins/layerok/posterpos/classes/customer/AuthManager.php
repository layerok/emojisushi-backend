<?php

namespace Layerok\PosterPos\Classes\Customer;

use Layerok\PosterPos\Models\User;
use RainLab\User\Classes\AuthManager as AuthManagerBase;

class AuthManager extends AuthManagerBase
{
    protected $userModel = User::class;


}
