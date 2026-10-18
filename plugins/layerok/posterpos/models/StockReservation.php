<?php

namespace Layerok\PosterPos\Models;

use October\Rain\Database\Model;

/**
 * Stock one order took. Released rows have been put back.
 *
 * @property int $spot_id
 * @property int $product_id
 * @property int $quantity
 * @property int|null $online_order_id
 * @property int|null $incoming_order_id
 * @property string $status
 */
class StockReservation extends Model
{
    const STATUS_RESERVED = 'reserved';
    const STATUS_RELEASED = 'released';

    protected $table = 'layerok_posterpos_stock_reservations';

    public $fillable = [
        'spot_id',
        'product_id',
        'quantity',
        'online_order_id',
        'incoming_order_id',
        'status',
    ];
}
