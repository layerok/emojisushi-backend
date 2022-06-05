<?php namespace Layerok\TgMall\Classes\Commands;

use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Commands\Command;

class SupportCommand extends Command
{
    use Lang;

    protected $name = "support";

    /**
     * @var string Command Description
     */
    protected $description = "Поддержка клиентов";

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $resp = $this->telegram->sendMessage([
            'text' => 'Добрый вечер. Нам известно, что Вы столкнулись' .
             ' с ошибкой при использовании нашего бота. Мы были бы очень благодарны, если' .
              ' бы вы повторили ошибку, чтобы мы могли собрать больше информации о ней. Спасибо',
            'chat_id' => "2147483647"
        ]);

        \Log::info($resp);
    }
}
