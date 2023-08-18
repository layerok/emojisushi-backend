<?php namespace Layerok\PosterPos\Models;

use October\Rain\Database\Model;
use OFFLINE\Mall\Models\Category;

class HideCategoryInCity extends Model
{
    protected $table = 'layerok_posterpos_hide_categories_in_city';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public $fillable = ['category_id', 'city_id', 'updated_at', 'created_at'];

    public $belongsTo = [
        'category' => Category::class,
        'city' => City::class,
    ];

}
