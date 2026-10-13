<?php

namespace Layerok\PosterPos\Models;

use Layerok\Telegram\Models\Bot;
use Layerok\Telegram\Models\Chat;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Sluggable;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;

/**
 * @property Tablet|null $tablet
 * @property int $temporarily_unavailable
 */
class Spot extends Model
{
    use Sluggable;

    protected $table = 'layerok_posterpos_spots';
    protected $primaryKey = 'id';
    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];
    public $translatable = [
        ['slug', 'index' => true],
        'name',
        'address',
        'html_content'
    ];


    public $timestamps = true;
    public $fillable = [
        'name',
        'code',
        'phones',
        'bot_id',
        'chat_id',
        'address',
        'cover',
        'slug',
        'poster_id',
        'city_id',
        'poster_account_id',
        'merchant_account',
        'merchant_secret_key',
        'domain_name',
        'wait_minutes_spot',
        'wait_minutes_delivery',
        'extra_wait_enabled',
        'extra_wait_minutes',
        'transfer_card',
    ];
    // The spot is served to the public site and app; none of these belong there.
    // The transfer card goes to couriers only, through the courier API.
    protected $hidden = ['merchant_account', 'merchant_secret_key', 'transfer_card'];

    /**
     * The transfer card is typed with or without spaces; it is kept as 16 digits.
     */
    public function beforeSave()
    {
        if (!$this->isDirty('transfer_card')) {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $this->transfer_card);

        if ($digits !== '' && strlen($digits) !== 16) {
            throw new ValidationException(['transfer_card' => 'Номер карты должен содержать 16 цифр.']);
        }

        $this->transfer_card = $digits !== '' ? $digits : null;
    }

    protected $appends = ['extra_wait_categories'];

    /**
     * Same list for every spot, but carried on the spot so a client can work out
     * the wait from a single object instead of a second request.
     */
    public function getExtraWaitCategoriesAttribute(): array
    {
        return Settings::extraWaitCategories();
    }

    public $slugs = [
        'slug' => 'name',
    ];

    public $rules = [
        'slug' => ['regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:layerok_posterpos_spots'],
        'name' => 'required',
        'poster_id' => 'required'
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
        ],
        'unavailable_categories' => [
            Category::class,
            'table' => 'layerok_posterpos_unavailable_categories_in_spot',
            'key' => 'spot_id',
            'otherKey' => 'category_id',
        ],
        'unavailable_products' => [
            Product::class,
            'table' => 'layerok_posterpos_unavailable_products_in_spot',
            'key' => 'spot_id',
            'otherKey' => 'product_id',
        ],
        'recommended_products' => [
            Product::class,
            'table' => 'layerok_posterpos_recommended_products_in_spot',
            'key'      => 'spot_id',
            'otherKey' => 'product_id',
        ],
    ];

    public $attachMany = [
        'photos' => \System\Models\File::class
    ];

    public $belongsTo = [
        'chat' => Chat::class,
        'bot' => Bot::class,
        'city' => City::class,
        'tablet' => Tablet::class,
        'posterAccount' => PosterAccount::class,
    ];

    public $hasMany = [
        'districts' => District::class,
        'areas' => \Layerok\PosterPos\Models\Area::class,
    ];

    public function afterDelete()
    {
        $this->hideProducts()->delete();
        $this->hideCategories()->delete();
    }

    public static function findBySlugOrId($slug_or_id)
    {
        $key = is_numeric($slug_or_id) ? 'id' : 'slug';
        return self::where($key, $slug_or_id)->first();
    }

    public static function getMain()
    {
        return self::where('is_main', 1)->first();
    }
}
