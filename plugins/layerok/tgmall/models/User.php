<?php namespace Layerok\TgMall\Models;

use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use OFFLINE\Mall\Models\Customer;
use \System\Models\File as SystemFile;

class User extends Model
{
    use Validation;
    protected $table = 'layerok_tgmall_users';
    protected $primaryKey = 'id';

    public $fillable = [
        'username',
        'firstname',
        'lastname',
        'chat_id',
        'customer_id',
        'phone'
    ];

    public $belongsTo = [
        'customer' => Customer::class,
    ];

    public $hasOne = [
        'state' => State::class,
    ];

    public $rules = [
        'customer_id'   => 'required|exists:offline_mall_customers,id',
    ];

}
