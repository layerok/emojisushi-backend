<?php namespace Layerok\BaseCode;

use Backend;
use Layerok\BaseCode\Events\TgMallHandlersExtend;
use Layerok\BaseCode\Events\TgMallKeyboardMainBeforeBuild;
use Layerok\BaseCode\Events\TgMallOrderHandler;
use Layerok\BaseCode\Events\TgMallStartCommandStarting;
use Layerok\BaseCode\Events\TgMallStateCreated;
use OFFLINE\Mall\Classes\Utils\Money;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\Currency;
use System\Classes\PluginBase;
use Event;
use Config;
use Validator;
/**
 * BaseCode Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['OFFLINE.Mall'];
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'BaseCode',
            'description' => 'No description provided yet...',
            'author'      => 'Layerok',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $money = $this->app->make(Money::class);
        CartProduct::extend(function($model) use ($money) {
            $model->addDynamicMethod('getTotalFormattedPrice', function() use ($model, $money) {
                return $money->format(
                    $model->price()->price * $model->quantity,
                    null,
                    Currency::$defaultCurrency
                );
            });
        });
        Cart::extend(function($model) use ($money) {
            $model->addDynamicMethod('getTotalFormattedPrice', function() use ($model, $money) {
                return $money->format(
                    $model->totals()->totalPostTaxes(),
                    null,
                    Currency::$defaultCurrency
                );
            });

            $model->addDynamicMethod('getTotalQuantity', function () use ($model) {
                $total = $model->products()->get()->reduce(function($carry, $item) {
                    return $carry + $item->quantity;
                }) ;

                return $total ?: 0;
            });
        });




    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Validator::extend('phoneUa', function($attribute, $value, $parameters) {
            $regex = "/^(((\+?)(38))\s?)?(([0-9]{3})|(\([0-9]{3}\)))(\-|\s)?(([0-9]{3})(\-|\s)?
        ([0-9]{2})(\-|\s)?([0-9]{2})|([0-9]{2})(\-|\s)?([0-9]{2})(\-|\s)?
        ([0-9]{3})|([0-9]{2})(\-|\s)?([0-9]{3})(\-|\s)?([0-9]{2}))$/";

            return preg_match($regex, $value);
        });


        Event::subscribe(new TgMallOrderHandler());
        Event::subscribe(new TgMallStateCreated());
        Event::subscribe(new TgMallStartCommandStarting());
        Event::subscribe(new TgMallHandlersExtend());
        Event::subscribe(new TgMallKeyboardMainBeforeBuild());
        // debug to telegram
        if(env('DEBUG_TO_TELEGRAM')) {

            Config::set(
                'logging.channels',
                array_merge(
                    Config::get('logging.channels'),
                    [
                        'telegram' => [
                            'driver' => 'monolog',
                            'level'  => 'info',
                            'handler' => \Monolog\Handler\TelegramBotHandler::class,
                            'with'    => [
                                'apiKey' => env('MY_LOG_BOT_TOKEN'),
                                'channel' => env('MY_LOG_BOT_CHAT_ID')
                            ],
                            'tap' => [
                                \Layerok\BaseCode\Taps\CustomizeMonologTelegramHandler::class
                            ]
                        ]
                    ]
                )
            );

            Config::set( 'logging.channels.stack.channels', ['daily', 'telegram'] );
            Config::set('logging.default', 'stack');
        }
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Layerok\BaseCode\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'layerok.basecode.some_permission' => [
                'tab' => 'BaseCode',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'basecode' => [
                'label'       => 'BaseCode',
                'url'         => Backend::url('layerok/basecode/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['layerok.basecode.*'],
                'order'       => 500,
            ],
        ];
    }
}
