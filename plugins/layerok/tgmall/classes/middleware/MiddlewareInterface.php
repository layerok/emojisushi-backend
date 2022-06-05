<?php

namespace Layerok\TgMall\Classes\Middleware;

interface MiddlewareInterface
{
    public function run():bool;
    public function onFailed():void;
}
