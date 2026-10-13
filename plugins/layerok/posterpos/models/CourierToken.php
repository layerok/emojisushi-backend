<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * One courier app session. Only the SHA-256 of the token is stored; the token
 * itself exists on the courier's device alone.
 */
class CourierToken extends Model
{
    public $table = 'layerok_posterpos_courier_tokens';

    protected $fillable = [
        'courier_id',
        'token_hash',
        'device_name',
        'last_used_at',
        'expires_at',
    ];

    protected $dates = ['last_used_at', 'expires_at'];

    public $belongsTo = [
        'courier' => Courier::class,
    ];
}
