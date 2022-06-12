<?php

namespace Layerok\PosterPos\Classes;

class PosterUtils
{
    public static function parseProducts($products): array
    {
        $posterProducts = new PosterProducts();
        $posterProducts->addCartProducts($products);
        return $posterProducts->all();
    }

    /**
     * @param $params $params [
     * @var mixed $comment - Опциональный параметр
     * @var mixed $change - Опциональный параметр
     * @var mixed $payment_method_name - Опциональный параметр
     * @var mixed $delivery_method_name - Опциональный параметр
     * ]
     */

    public static function getComment($params): string
    {
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
            $comment .= "Приготовить сдачу с: ".$params['change'] . $sep;
        }

        if (is($params, 'payment_method_name')) {
            $comment .= "Способ оплаты: " . $params['payment_method_name'] . $sep;
        }

        if (is($params, 'delivery_method_name')) {
            $comment .= "Способ доставки: " . $params['delivery_method_name'] . $sep;
        }
        return substr($comment, 0, -4);
    }

}
