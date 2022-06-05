<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * Tablet Model
 */
class Tablet extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'layerok_posterpos_tablets';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['name', 'spot_id', 'tablet_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'tablet_id' => 'required',
        'spot_id' => 'required',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'spot' => \Layerok\PosterPos\Models\Spot::class,
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
}
