<?php
namespace Layerok\BaseCode\Taps;

class CustomizeMonologTelegramHandler
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        // I decided to cut out the stack trace from the message
        // because some logs can be to long for telegram
        // and telegram trows an error in this case
        // and you won't get any information about log in the telegram
        // except the message was too long to send
        $handlers = $logger->getHandlers();
        foreach ($handlers as $handler) {
            if ($handler instanceof \Monolog\Handler\TelegramBotHandler) {
                $handler->pushProcessor(function ($record) {
                    $new_record = $record;
                    $new_record['message'] = preg_replace('/Stack\strace.*/s', '', $record['message']);
                    return $new_record;
                });
            }
        }
    }
}
