<?php

namespace Layerok\PosterPos\Classes;

class PosterUtils
{
    /**
     * @param $params $params [
     * @var mixed $comment - Опциональный параметр
     * @var mixed $change - Опциональный параметр
     * @var mixed $payment_method_name - Опциональный параметр
     * @var mixed $delivery_method_name - Опциональный параметр
     * ]
     */

    public static function getComment($params, $lang_resolver = null): string
    {
        $translates = [
            'change' => 'Приготовить сдачу с',
            'payment_method' => 'Способ оплаты',
            'delivery_method' => 'Способ доставки',
        ];
        if(!$lang_resolver) {
            $lang_resolver = function($key) use($translates) {
                return $translates[$key];
            };
        }
        $comment = "";

        function is($p, $key)
        {
            if (isset($p[$key]) && !empty($p[$key])) {
                return true;
            }
            return false;
        }

        $sep = " || ";

        if (is($params, 'comment')) {
            $comment .= $params['comment'] . " || ";
        }

        if (is($params, 'change')) {
            $comment .= $lang_resolver('change') . ": ".$params['change'] . $sep;
        }

        if (is($params, 'payment_method_name')) {
            $comment .=  $lang_resolver('payment_method') . ": " . $params['payment_method_name'] . $sep;
        }

        if (is($params, 'delivery_method_name')) {
            $comment .= $lang_resolver('delivery_method') . ": " . $params['delivery_method_name'] . $sep;
        }
        return substr($comment, 0, -4);
    }

}
