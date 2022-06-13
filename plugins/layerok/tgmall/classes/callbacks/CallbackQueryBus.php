<?php namespace Layerok\TgMall\Classes\Callbacks;

use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Webhook;
use Layerok\TgMall\Models\User as TelegramUser;
use October\Rain\Support\Traits\Singleton;
use Telegram\Bot\Answers\AnswerBus;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Config;
use Exception;

class CallbackQueryBus extends AnswerBus
{
    use Singleton;
    use CallbackData;

    protected $telegram;

    protected $handlers;

    protected $telegramUser;

    public $webhook;

    /** @var Update */
    protected $update;

    public function __construct(Api $telegram = null)
    {
        $this->telegram = $telegram;
    }

    public function setTelegramUser(TelegramUser $telegramUser): self
    {
        $this->telegramUser = $telegramUser;
        return $this;
    }

    public function setUpdate(Update $update): self
    {
        $this->update = $update;
        return $this;
    }

    public function handle(): void
    {
        [$name, $arguments] = $this->parse($this->update);

        $this->make($name, $arguments);
    }

    public function make($name, $arguments) {
        $handler = $this->find($name);

        if(!isset($handler)) {
            \Log::error('Handler [' . $name . '] is not found');
            return;
        }
        $handler->setTelegramUser($this->telegramUser);
        $handler->setWebhook($this->webhook);
        $handler->make($this->telegram, $this->update, $arguments);
    }

    public function parse(Update $update): array
    {
        $data = $update->getCallbackQuery()
            ->getData();

        return $this->parseCallbackData($data);

    }

    public function find($name)
    {
        return $this->handlers[$name] ??
            collect($this->handlers)->filter(function ($command) use ($name) {
                return $command instanceof $name;
            })->first() ?? null;
    }


    public function addHandlers($handlers) {
        foreach($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    public function addHandler($handler) {
        $handler = $this->resolveHandler($handler);

        $this->handlers[$handler->getName()] = $handler;
    }

    private function resolveHandler($handler)
    {
        $handler = $this->makeHandlerObj($handler);

        if (! ($handler instanceof HandlerInterface)) {
            throw new Exception(
                sprintf(
                    'Handler class "%s" should be an instance of "Layerok\TgMall\Classes\Callbacks\HandlerInterface"',
                    get_class($handler)
                )
            );
        }

        return $handler;
    }

    private function makeHandlerObj($handler)
    {
        if (is_object($handler)) {
            return $handler;
        }
        if (! class_exists($handler)) {
            throw new Exception(
                sprintf(
                    'Handler class "%s" not found! Please make sure the class exists.',
                    $handler
                )
            );
        }

        if ($this->telegram->hasContainer()) {
            return $this->buildDependencyInjectedAnswer($handler);
        }

        return new $handler();
    }

    public function setWebhook(Webhook $webhook): self {
        $this->webhook = $webhook;
        return $this;
    }
}
