<?php

namespace Layerok\PosterPos\Classes;

use OFFLINE\Mall\Models\CartProduct;

class PosterProducts
{
    protected $items = [];


    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function add($name, $product_id, $count, $modificator_id = null): PosterProducts
    {
        $item = [
            'name' => $name,
            'product_id' => $product_id,
            'count' => $count
        ];

        if (isset($modificator_id)) {
            $item['modificator_id'] = $modificator_id;
        }

        $this->items[] = $item;
        return $this;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function addProduct($id, $name, $count): PosterProducts
    {
        if (!isset($count)) {
            return $this;
        }
        if (intval($count) > 0) {
            $this->add($name, $id, intval($count));
        }
        return $this;
    }

    public function addCartProduct(CartProduct $cartProduct): PosterProducts
    {
        $modificator_id = null;
        if (isset($cartProduct['variant_id'])) {
            $product = $cartProduct->product()->first();
            $variant = $cartProduct->getItemDataAttribute();
            $modificator_id = $variant['poster_id'];
        } else {
            $product = $cartProduct->getItemDataAttribute();
        }

        return $this->add(
            $product['name'],
            $product['poster_id'],
            $cartProduct['quantity'],
            $modificator_id
        );
    }

    public function addCartProducts($products): PosterProducts
    {
        foreach ($products as $p) {
            $this->addCartProduct($p);
        }
        return $this;
    }



}
