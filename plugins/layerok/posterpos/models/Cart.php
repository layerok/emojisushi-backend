<?php
namespace Layerok\PosterPos\Models;

use Layerok\PosterPos\Classes\Traits\Cart\CartSession;
use \OFFLINE\Mall\Models\Cart as CartBase;

class Cart extends CartBase {
    use CartSession;
}
