<?php namespace Layerok\PosterPos\Models;

use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use OFFLINE\Mall\Models\Product;


class HideProduct extends Model
{
    protected $table = 'layerok_posterpos_hide_products_in_spot';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $fillable = ['product_id', 'spot_id'];

    public $belongsTo = [
        'product' => Product::class,
        'spot' => Spot::class,
    ];

}
