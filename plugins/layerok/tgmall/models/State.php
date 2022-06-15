<?php namespace Layerok\TgMall\Models;

use October\Rain\Database\Model;
use OFFLINE\Mall\Models\PaymentMethod;
use Layerok\TgMall\Models\User as TelegramUser;


class State extends Model
{
    protected $table = 'layerok_tgmall_states';
    protected $primaryKey = 'id';

    protected $jsonable = ['state'];

    public $fillable = [
        'user_id',
        'state',
    ];

    public $belongsTo = [
        'user' => TelegramUser::class
    ];

    public $timestamps = true;

    public function setStateValue($key, $value)
    {
        $newState = array_merge(
            $this->state ?? [],
            [$key => $value]
        );
        $this->state = $newState;
        $this->save();
    }

    public function getStateValue($key)
    {
        $state = $this->state;
        if (!isset($state[$key])) {
            return null;
        }
        return $state[$key];
    }

    public function setMessageHandler($handler)
    {
        $this->setStateValue('message_handler', $handler);
    }

    public function setSpotId($id)
    {
        $this->setStateValue('spot_id', $id);
    }

    public function setCallbackHandler($handler)
    {
        $this->setStateValue('callback_handler', $handler);
    }

    public function setDeleteMsgInCategory($info)
    {
        $this->setStateValue('delete_msg_in_category', $info);
    }

    public function setCartTotalMsg($info)
    {
        $this->setStateValue('cart_total_msg', $info);
    }

    public function setCartCountMsg($info)
    {
        $this->setStateValue('cart_count_msg', $info);
    }

    public function getSpotId() {
        return $this->getStateValue('spot_id');
    }

    public function getMessageHandler()
    {
        return $this->getStateValue('message_handler');
    }

    public function getDeleteMsgInCategory()
    {
        return $this->getStateValue('delete_msg_in_category');
    }

    public function getCartTotalMsg()
    {
        return $this->getStateValue('cart_total_msg');
    }

    public function getCartCountMsg()
    {
        return $this->getStateValue('cart_count_msg');
    }


    public function setCartTotalMsgTotal($total)
    {
        $this->setCartTotalMsg(
            array_merge(
                $this->getCartTotalMsg() ?? [],
                ['total' => $total]
            )
        );
    }

    public function setCartCountMsgCount($count)
    {
        $this->setCartCountMsg(
            array_merge(
                $this->getCartCountMsg() ?? [],
                ['count' => $count]
            )
        );
    }

    public function getOrderInfo()
    {
        return $this->getStateValue('order_info');
    }

    public function setOrderInfo($info)
    {
        $this->setStateValue('order_info', $info);
    }

    public function mergeOrderInfo($info)
    {
        $newState = array_merge(
            $this->state ?? [],
            ['order_info' => array_merge(
                $this->getOrderInfo() ?? [],
                $info
            )]
        );
        $this->state = $newState;
        $this->save();
    }

    public function getOrderInfoValue($key)
    {
        $orderInfo = $this->getOrderInfo();

        if (!isset($orderInfo)) {
            return null;
        }

        if (!isset($orderInfo[$key])) {
            return null;
        }

        return $orderInfo[$key];
    }

    public function setOrderInfoValue($key, $value)
    {
        $this->mergeOrderInfo([
            $key => $value
        ]);
    }

    public function getOrderInfoComment()
    {
        return $this->getOrderInfoValue('comment');
    }

    public function getOrderInfoChange()
    {
        return $this->getOrderInfoValue('change');
    }

    public function getOrderInfoPaymentMethodId()
    {
        return $this->getOrderInfoValue('payment_method_id');
    }

    public function getOrderInfoDeliveryMethodId()
    {
        return $this->getOrderInfoValue('delivery_method_id');
    }

    public function getOrderInfoAddress()
    {
        return $this->getOrderInfoValue('address');
    }

    public function getOrderInfoSticksCount()
    {
        return $this->getOrderInfoValue('sticks_count');
    }

    public function setOrderInfoSticksCount($value)
    {
        $this->setOrderInfoValue('sticks_count', $value);
    }

    public function setOrderInfoPaymentMethodId($value)
    {
        $this->setOrderInfoValue('payment_method_id', $value);
    }

    public function setOrderInfoDeliveryMethodId($value)
    {
        $this->setOrderInfoValue('delivery_method_id', $value);
    }

    public function setOrderInfoComment($value)
    {
        $this->setOrderInfoValue('comment', $value);
    }

    public function setOrderInfoAddress($value)
    {
        $this->setOrderInfoValue('address', $value);
    }

    public function setOrderInfoPhone($value)
    {
        $this->setOrderInfoValue('phone', $value);
    }

    public function setOrderInfoChange($value)
    {
        $this->setOrderInfoValue('change', $value);
    }

    public function getCustomer() {
        return $this->user->customer;
    }


}
