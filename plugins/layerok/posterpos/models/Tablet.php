<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * @property string $name
 * @property string $tablet_id
 * @property string $spot_id
 * @property string $poster_account_id
 * @property PosterAccount|null $poster_account
 * @property PosterAccount|null $spot
 */
class Tablet extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'layerok_posterpos_tablets';

    protected $guarded = ['*'];

    protected $fillable = ['name', 'spot_id', 'tablet_id', 'poster_account_id'];

    public $rules = [
        'tablet_id' => 'required',
    ];

    protected $casts = [];

    protected $jsonable = [];

    protected $appends = [];

    protected $hidden = [];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public $hasOne = [];
    public $hasMany = [
        'spots' => \Layerok\PosterPos\Models\Spot::class,
    ];
    public $belongsTo = [
        'poster_account' => PosterAccount::class,
    ];

    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
}
