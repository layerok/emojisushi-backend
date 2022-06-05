<?php namespace Layerok\TgMall\Classes\Callbacks;


class NoopHandler extends Handler
{

    protected $name = "noop";

    public function run()
    {
        return;
    }
}
