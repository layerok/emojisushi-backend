<?php namespace Layerok\PosterPos\Models;

use Model;

/**
 * One line of the operator's "recent events" feed, written by the Poster webhook.
 */
class DeliveryEvent extends Model
{
    public $table = 'layerok_posterpos_delivery_events';

    public const TYPE_OPENED = 'opened';
    public const TYPE_COURIER_ASSIGNED = 'courier_assigned';
    public const TYPE_COURIER_CHANGED = 'courier_changed';
    public const TYPE_COURIER_REMOVED = 'courier_removed';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_CLOSED = 'closed';
    public const TYPE_REMOVED = 'removed';
    public const TYPE_CANCELED = 'canceled';
    public const TYPE_INCOMING_NEW = 'incoming_new';
    public const TYPE_INCOMING_ACCEPTED = 'incoming_accepted';
    public const TYPE_INCOMING_CANCELED = 'incoming_canceled';

    protected $fillable = [
        'type',
        'transaction_id',
        'incoming_order_id',
        'spot_poster_id',
        'courier_id',
        'previous_courier_id',
        'processing_status',
        'address',
        'client',
        'happened_at',
    ];

    protected $dates = ['happened_at'];
}
