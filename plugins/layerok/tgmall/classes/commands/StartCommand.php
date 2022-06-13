<?php namespace Layerok\TgMall\Classes\Commands;

use Layerok\TgMall\Features\Index\MainMenuKeyboard;
use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Commands\Command;
use Event;

class StartCommand extends Command
{
    use Lang;

    protected $name = "start";

    /**
     * @var string Command Description
     */
    protected $description = "Команда для начала работы";

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $stop = Event::fire('tgmall.command.start.starting', [$this], true);
        if($stop) {
            return;
        }
        $update = $this->getUpdate();
        $from = $update->getMessage()
            ->getChat();

        $text = sprintf(
            self::lang('texts.welcome'),
            $from->firstName
        );

        $markup = new MainMenuKeyboard();

        $this->replyWithMessage([
            'text' => $text,
            'reply_markup' => $markup->getKeyboard()
        ]);
    }
}
