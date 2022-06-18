<?php namespace Layerok\PosterPos\Models;

use October\Rain\Database\Model;
use OFFLINE\Mall\Models\Category;


class HideCategory extends Model
{
    protected $table = 'layerok_posterpos_hide_categories_in_spot';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public $fillable = ['category_id', 'spot_id', 'updated_at', 'created_at'];

    public $belongsTo = [
        'category' => Category::class,
        'spot' => Spot::class,
    ];

}
