<?php namespace Layerok\PosterPos\Models;

use Layerok\Telegram\Models\Bot;
use Layerok\Telegram\Models\Chat;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;


class Spot extends Model
{

    protected $table = 'layerok_posterpos_spots';
    protected $primaryKey = 'id';
    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];
    public $translatable = ['name', 'address'];


    public $timestamps = true;
    public $fillable = [
        'name',
        'code',
        'phones',
        'bot_id',
        'chat_id',
        'address',
        'poster_id'
    ];


    public $belongsToMany = [
        'hideProducts'          => [
            Product::class,
            'table'    => 'layerok_posterpos_hide_products_in_spot',
            'key'      => 'spot_id',
            'otherKey' => 'product_id',
        ],
        'hideCategories' => [
            Category::class,
            'table' => 'layerok_posterpos_hide_categories_in_spot',
            'key' => 'spot_id',
            'otherKey' => 'category_id',
        ]
    ];

    public $belongsTo = [
        'chat' => Chat::class,
        'bot' => Bot::class,
    ];

    public $hasOne = [
        'tablet' => Tablet::class,
    ];

    public function getChatId()
    {
        return $this->telegram_chat_id;
    }

    public function getTabletId()
    {
        return $this->poster_spot_tablet_id;
    }

    public function afterDelete() {
        $this->hideProducts()->delete();
        $this->hideCategories()->delete();
    }

}
