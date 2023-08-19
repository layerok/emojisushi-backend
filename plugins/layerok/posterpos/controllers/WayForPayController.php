<?php

namespace Layerok\PosterPos\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maksa988\WayForPay\Facades\WayForPay;
use Telegram\Bot\Api;
use WayForPay\SDK\Domain\TransactionService;
use Layerok\PosterPos\Models\Spot;

class WayForPayController {
    public Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function __invoke() {
        $content = $this->request->getContent();
        $data = json_decode($content);

        return WayForPay::handleServiceUrl($data, function (TransactionService $transaction, $success) use($data) {
            $spot = Spot::findBySlugOrId(input('spot_id'));

            if($transaction->getReason()->isOK()) {
                $this->notify($spot, $transaction);
                $logMessage = sprintf(
                    '[WAYFORPAY] Status of order #%s  %s',
                    $transaction->getOrderReference(),
                    $transaction->getStatus()
                );
                Log::channel('single')->debug($logMessage );

                return $success();
            }

            $error = sprintf("[WAYFORPAY] Order number: %s. Code: %s. Message: %s",
                $transaction->getOrderReference(),
                $transaction->getReason()->getCode(),
                $transaction->getReason()->getMessage()
            );

            Log::error($error);
            return $error;
        });
    }

    public function notify(Spot $spot, TransactionService $transaction) {
        $api = new Api($spot->bot->token);

        if($transaction->isStatusApproved()) {
            $message = sprintf(
                "✅ Успішний платіж на сайті https://emojisushi.com.ua \n\nСума: %s %s \nНомер замовлення: %s",
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getOrderReference()
            );
        } else if($transaction->isStatusRefunded()) {
            $message = sprintf("Платіж повернуто \nСума: %s %s \nНомер замовлення: %s",
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getOrderReference()
            );
        } else if ($transaction->isStatusDeclined()) {
            $message = sprintf("Платіж скасовано \nСума: %s %s \nНомер замовлення: %s",
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getOrderReference()
            );
        } else if ($transaction->isStatusExpired()) {
            $message = sprintf(
                "Час на оплату вичерпано \nСума: %s %s \nНомер замовлення: %s",
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getOrderReference()
            );
        } else {
            $message = sprintf(
                "Статус платежу: %s \nСума: %s %s \nНомер замовлення: %s",
                $transaction->getStatus(),
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getOrderReference()
            );
        }
        // todo: don't simple send message, but reply to initial telegram message
        $api->sendMessage([
            'text' => $message,
            'parse_mode' => "html",
            'chat_id' => $spot->chat->internal_id
        ]);
    }
}
