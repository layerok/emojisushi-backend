<?php
namespace Layerok\TgMall\Classes\Traits;

trait Warn {
    public function warn($msg)
    {
        $msg = "[CallbackHandler] " . $msg;
        \Log::warning($msg);

    }

    public function error($msg) {
        $msg = "[CallbackHandler] " . $msg;
        \Log::error($msg);

    }
}
