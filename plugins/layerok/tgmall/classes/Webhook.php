<?php namespace Layerok\TgMall\Classes;

use Layerok\TgMall\Classes\Callbacks\CallbackQueryBus;
use Layerok\TgMall\Classes\Callbacks\NoopHandler;
use Layerok\TgMall\Features\Checkout\ConfirmOrderHandler;
use Layerok\Tgmall\Features\Cart\CartHandler;
use Layerok\Tgmall\Features\Category\CategoryItemHandler;
use Layerok\Tgmall\Features\Category\CategoryItemsHandler;
use Layerok\TgMall\Features\Checkout\CheckoutHandler;
use Layerok\TgMall\Features\Checkout\ChoseDeliveryMethodHandler;
use Layerok\TgMall\Features\Checkout\ChosePaymentMethodHandler;
use Layerok\TgMall\Features\Checkout\EnterPhoneHandler;
use Layerok\TgMall\Features\Checkout\LeaveCommentHandler;
use Layerok\TgMall\Features\Checkout\ListDeliveryMethodsHandler;
use Layerok\TgMall\Features\Checkout\ListPaymentMethodsHandler;
use Layerok\TgMall\Features\Checkout\PreConfirmOrderHandler;
use Layerok\TgMall\Features\Index\WebsiteHandler;
use Layerok\Tgmall\Features\Product\AddProductHandler;
use Layerok\TgMall\Features\Index\StartHandler;
use Layerok\TgMall\Classes\Commands\StartCommand;
use Layerok\TgMall\Classes\Traits\HasMaintenanceMode;
use Layerok\TgMall\Models\State;
use League\Event\Emitter;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\User;
use \Layerok\TgMall\Models\User as TelegramUser;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\HelpCommand;
use Telegram\Bot\Events\UpdateWasReceived;
use Log;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Objects\Update;
use Event;

class Webhook
{
    use HasMaintenanceMode;

    /** @var TelegramUser */
    public $telegramUser;

    /** @var State */
    public $state;

    /** @var Api */
    protected $api;

    public function __construct($botToken)
    {
        $this->api = new Api($botToken);
        $this->init();
    }

    public function init()
    {
        $this->addCommands();
        $this->addCallbackQueryHandlers();
        $this->addListeners();

        $this->api->commandsHandler(true);
    }

    public function addCommands()
    {
        $this->api->addCommands([
            StartCommand::class,
            HelpCommand::class
        ]);
    }

    public function addCallbackQueryHandlers()
    {
        CallbackQueryBus::instance()
            ->setTelegram($this->api)
            ->setWebhook($this)
            ->addHandlers([
                StartHandler::class,
                WebsiteHandler::class,
                CategoryItemsHandler::class,
                CategoryItemHandler::class,
                AddProductHandler::class,
                CartHandler::class,
                CheckoutHandler::class,
                NoopHandler::class,
                EnterPhoneHandler::class,
                ChosePaymentMethodHandler::class,
                ChoseDeliveryMethodHandler::class,
                ListPaymentMethodsHandler::class,
                ListDeliveryMethodsHandler::class,
                LeaveCommentHandler::class,
                PreConfirmOrderHandler::class,
                ConfirmOrderHandler::class,
            ]);
    }

    public function addListeners()
    {
        $emitter = new Emitter();

        $emitter->addListener(UpdateWasReceived::class, function($event) {
            $this->handleUpdate($event);
        });

        $this->api->setEventEmitter($emitter);
    }

    public function handleUpdate($event) {
        $update = $event->getUpdate();
        $telegram = $event->getTelegram();

        $this->telegramUser = $this->createUser($update);

        if(!isset($this->telegramUser)) {
            \Log::error("User was not created. We can't go further without this");
            $this->answerCallbackQuery($telegram, $update);
            return;
        }

        CallbackQueryBus::instance()
            ->setTelegram($telegram)
            ->setTelegramUser($this->telegramUser)
            ->setUpdate($update);

        $this->state = $this->createState();

        $stop = Event::fire('tgmall.state.created', [$this], true);

        if($stop) {
            $this->answerCallbackQuery($update);
            return;
        }

        $is_maintenance = $this->checkMaintenanceMode(
            $this->api,
            $update,
            $this->telegramUser
        );

        if ($is_maintenance) {
            $this->answerCallbackQuery($update);
            return;
        }

        if ($update->isType('callback_query')) {

            $this->state->setMessageHandler(null);

            CallbackQueryBus::instance()
                ->handle();

            $this->answerCallbackQuery($update);

        }

        if ($update->isType('message')) {

            if ($update->hasCommand()) {
                return;
            }

            $message_handler = $this->state->getMessageHandler();

            if (!isset($message_handler)) {
                return;
            }

            if (!class_exists($message_handler)) {
                \Log::error('message handler with [' . $message_handler . '] does not exist');
                return;
            }

            $handler = new $message_handler($telegram, $update, $this->state);
            $handler->start();
        }
    }

    public function createUser(Update $update) {
        $chat = $update->getChat();

        if($update->isType('callback_query')) {
            $from = $update->getCallbackQuery()
                ->getFrom();
        } else {
            $from = $update->getMessage()
                ->getFrom();
        }


        $telegramUser = TelegramUser::where('chat_id', '=', $chat->id)
            ->first();

        if(isset($telegramUser) &&
            isset($telegramUser->customer) &&
            isset($telegramUser->customer->user)) {
            return $telegramUser;
        }

        $firstName = empty($from->getFirstName()) ? 'Не указано': $from->firstName;
        $lastName = empty($from->getLastName()) ? 'Не указано': $from->lastName;
        $username = empty($from->getUsername()) ? 'Не указано': $from->username;
        $email = "notrealemail@notrealemail.com";
        $pass = "qweasdqweaasd";

        try {
            $user = User::create([
                'name' => $firstName,
                'surname' => $lastName,
                'email' => $email,
                'username' => $username,
                'password' => $pass,
                'password_confirmation' => $pass
            ]);

            $customer = new Customer();
            $customer->firstname = $firstName;
            $customer->lastname = $lastName;
            $customer->user_id = $user->id;
            $customer->save();

            return TelegramUser::create([
                'firstname' => $firstName,
                'lastname' => $lastName,
                'username' => $username,
                'chat_id' => $chat->id,
                'customer_id' => $customer->id
            ]);
        } catch (ValidationException $exception) {
            Log::error([
                'status' => 'error',
                'msg'    => (string)$exception,
                'errors' => $exception->getErrors()
            ]);
            return null;
        }
    }

    public function createState(): State
    {
        if (isset($this->telegramUser->state)) {
            return $this->telegramUser->state;
        }
        return State::create(
            [
                'user_id' => $this->telegramUser->id,
            ]
        );
    }

    public function answerCallbackQuery($update)
    {
        try {
            if($update->isType('callback_query')) {
                $this->api->answerCallbackQuery([
                    'callback_query_id' => $update->getCallbackQuery()->id,
                ]);
            }
        } catch (TelegramResponseException $e) {
            Log::error($e);
        }
    }

    public function sendMessage($params) {
        try {
            $this->api->sendMessage(
                array_merge($params, ['chat_id' => $this->getChatId()])
            );
        } catch (\Exception $e) {
            \Log::error("Caught Exception ('{$e->getMessage()}')\n{$e}\n");
        }

    }

    public function getChatId()
    {
        return $this->telegramUser->chat_id;
    }



}
