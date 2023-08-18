<?php namespace Layerok\PosterPos\Models;

use October\Rain\Database\Model;
use OFFLINE\Mall\Models\Product;


class HideProductInCity extends Model
{
    protected $table = 'layerok_posterpos_hide_products_in_city';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $fillable = ['product_id', 'city_id'];

    public $belongsTo = [
        'product' => Product::class,
        'city' => City::class,
    ];

}
