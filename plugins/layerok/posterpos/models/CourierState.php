<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * The part of a courier's state only the courier app knows: the order being
 * delivered right now («Везу сейчас») and whether they are heading back to the
 * spot. Whether they are on a trip at all comes from Poster (orders at 40).
 */
class CourierState extends Model
{
    public $table = 'layerok_posterpos_courier_states';

    protected $fillable = [
        'courier_id',
        'current_transaction_id',
        'returning',
        'reported_at',
    ];

    protected $casts = [
        'returning' => 'boolean',
    ];

    protected $dates = ['reported_at'];

    public $belongsTo = [
        'courier' => Courier::class,
    ];
}
