<?php namespace Layerok\PosterPos\Models;

use Layerok\Restapi\Classes\Sort\Category;
use Model;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;

/**
 * @property string $access_token
 * @property int $id
 * @property string $account_name
 * @property string $url
 * @property string $application_secret
 * @property string $application_id
 */
class PosterAccount extends Model
{
    public $fillable = [
        'access_token',
        'account_name',
        'url',
        'application_secret',
        'application_id'
    ];

    public $hasMany = [
        'spots' => Spot::class,
        'tablets' => Tablet::class
    ];

    public $morphedByMany = [
        'posts'  => [Product::class, 'name' => 'poster_accountable'],
        'videos' => [Variant::class, 'name' => 'poster_accountable'],
        'categories' => [Category::class, 'name' => 'poster_accountable'],
    ];

    public $table = 'layerok_posterpos_poster_accounts';
}
