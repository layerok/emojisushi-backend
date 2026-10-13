<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * A Poster receipt left out of a courier's pay — the payment page's "delete",
 * since the receipt itself lives in Poster.
 */
class CourierExcludedReceipt extends Model
{
    public $table = 'layerok_posterpos_courier_excluded_receipts';

    protected $fillable = [
        'transaction_id',
        'date',
        'courier_id',
        'excluded_by',
    ];
}
