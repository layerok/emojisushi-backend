<?php

namespace Layerok\BaseCode\Classes\Traits;

use Lang as Facade;

trait Lang
{
    public static function lang($key, $params = [])
    {
        $pluginPrefix = "layerok.basecode::lang.telegram.";
        return Facade::get($pluginPrefix . $key, $params);
    }


}
