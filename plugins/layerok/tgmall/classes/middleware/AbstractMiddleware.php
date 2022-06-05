<?php

namespace Layerok\TgMall\Classes\Middleware;

use OFFLINE\Mall\Models\Customer;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * @var Api
     */
    protected $telegram;
    /**
     * @var Update
     */
    protected $update;

    /** @var Customer */
    protected $customer;

    public function make(Api $telegram, Update $update)
    {
        $this->update = $update;
        $this->telegram = $telegram;
        $chat = $update->getChat();

        $this->customer = Customer::where('tg_chat_id', '=', $chat->id)->first();
    }

    abstract public function run():bool;
}
