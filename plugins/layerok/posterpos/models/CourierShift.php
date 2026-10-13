<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * One courier's pay for one day. While open it only holds the km typed in;
 * once closed it holds the frozen numbers and the list of what was paid.
 */
class CourierShift extends Model
{
    public $table = 'layerok_posterpos_courier_shifts';

    protected $fillable = [
        'date',
        'courier_id',
        'km',
        'rate_per_km',
        'price_per_delivery',
        'receipts_count',
        'tasks_count',
        'total',
        'items',
        'closed_at',
        'closed_by',
    ];

    protected $jsonable = ['items'];

    protected $dates = ['closed_at'];

    public $belongsTo = [
        'courier' => Courier::class,
    ];

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }
}
