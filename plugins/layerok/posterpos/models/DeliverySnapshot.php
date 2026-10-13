<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * Last state of a delivery receipt as the webhook saw it.
 */
class DeliverySnapshot extends Model
{
    public $table = 'layerok_posterpos_delivery_snapshots';

    protected $fillable = [
        'transaction_id',
        'spot_poster_id',
        'courier_id',
        'status',
        'processing_status',
    ];
}
