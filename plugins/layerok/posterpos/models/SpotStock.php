<?php

namespace Layerok\PosterPos\Models;

use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Product;

/**
 * A limited amount of a product at one spot. Online orders take from it, and at
 * zero the product shows as unavailable there. A product without a row is not
 * limited at all.
 *
 * @property int $spot_id
 * @property int $product_id
 * @property int $amount
 */
class SpotStock extends Model
{
    use Validation;

    protected $table = 'layerok_posterpos_spot_stocks';

    public $fillable = ['spot_id', 'product_id', 'amount'];

    public $rules = [
        'product_id' => 'required',
        'amount' => 'required|integer|min:0',
    ];

    public $attributeNames = [
        'product_id' => 'Товар',
        'amount' => 'Количество',
    ];

    public $belongsTo = [
        'spot' => Spot::class,
        'product' => Product::class,
    ];

    public function beforeSave()
    {
        $taken = static::where('spot_id', $this->spot_id)
            ->where('product_id', $this->product_id)
            ->where('id', '!=', (int) $this->id)
            ->exists();

        if ($taken) {
            throw new ValidationException(['product' => 'Этот товар уже ограничен в этом заведении.']);
        }
    }
}
