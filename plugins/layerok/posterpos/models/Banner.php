<?php namespace Layerok\PosterPos\Models;

use Model;
use OFFLINE\Mall\Models\Product;
use System\Models\File;

class Banner extends Model
{
    public $table = 'layerok_posterpos_banners';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'is_active',
        'title'
    ];

    public $belongsTo = [
        'product' => Product::class,
    ];

    public $attachOne = [
        'image' => File::class,
        'image_small' => File::class,
    ];
}
