<?php namespace Layerok\PosterPos\Models;

use Carbon\Carbon;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Model;

/**
 * A job a courier did that is not a Poster receipt: Довоз, Закупка or Магазин.
 * Paid as one delivery. Couriers will create these from the app; until then an
 * admin can add them on the payment page.
 */
class CourierTask extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;

    public $table = 'layerok_posterpos_courier_tasks';

    public const TYPE_DOVOZ = 'dovoz';
    public const TYPE_ZAKUPKA = 'zakupka';
    public const TYPE_MAGAZIN = 'magazin';

    public const TYPES = [
        self::TYPE_DOVOZ => 'Довоз',
        self::TYPE_ZAKUPKA => 'Закупка',
        self::TYPE_MAGAZIN => 'Магазин',
    ];

    public const SOURCE_APP = 'app';
    public const SOURCE_ADMIN = 'admin';

    protected $fillable = [
        'courier_id',
        'spot_id',
        'type',
        'details',
        'comment',
        'performed_at',
        'source',
        'created_by',
        'client_uuid',
    ];

    protected $dates = ['performed_at', 'deleted_at'];

    public $rules = [
        'courier_id' => 'required|exists:layerok_posterpos_couriers,id',
        'type' => 'required|in:dovoz,zakupka,magazin',
        'details' => 'nullable|max:255',
        'comment' => 'nullable|max:2000',
        'performed_at' => 'required|date',
        'source' => 'in:app,admin',
        'client_uuid' => 'nullable|uuid',
    ];

    public $belongsTo = [
        'courier' => Courier::class,
        'spot' => Spot::class,
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    /**
     * Tasks done on one local (Kyiv) day. performed_at is stored in UTC like
     * every other timestamp, so the day boundaries are converted first.
     */
    public function scopeOnLocalDate($query, string $date)
    {
        return $this->scopeBetweenLocalDates($query, $date, $date);
    }

    public function scopeBetweenLocalDates($query, string $from, string $to)
    {
        $start = Carbon::parse($from, OpenDeliveries::TIMEZONE)->startOfDay()->utc();
        $end = Carbon::parse($to, OpenDeliveries::TIMEZONE)->endOfDay()->utc();

        return $query->whereBetween('performed_at', [$start, $end]);
    }

    public function localDate(): string
    {
        return $this->performed_at->copy()->setTimezone(OpenDeliveries::TIMEZONE)->toDateString();
    }
}
