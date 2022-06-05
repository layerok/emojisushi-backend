<?php
namespace Layerok\TgMall;

use Layerok\TgMall\Classes\Boot\Events;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Models\File as TelegramFile;
use Layerok\TgMall\Models\User as TelegramUser;

use OFFLINE\Mall\Classes\Utils\Money;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\Product;
use System\Classes\PluginBase;
use System\Models\File as SystemFile;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\Message;
use Request;
use Validator;
use Event;
class Plugin extends PluginBase
{
    public $require = ['OFFLINE.Mall'];
    use Lang;

    public function boot() {
        // to change host for assets
        if (!empty( env('NGROK_URL') ) && Request::instance()->server->has('HTTP_X_ORIGINAL_HOST')) {
            $this->app['url']->forceRootUrl(env('NGROK_URL'));
        }

        Events::boot();
    }

    public function register()
    {
        $this->registerConsoleCommand('create:tg.mall.handler', \Layerok\TgMall\Console\CreateCallbackHandler::class);
        $this->registerConsoleCommand('create:tg.mall.keyboard', \Layerok\TgMall\Console\CreateKeyboard::class);
        $money = $this->app->make(Money::class);

        SystemFile::extend(function($model) {
            $model->hasOne['tg'] = [TelegramFile::class, 'key' => 'system_file_id'];

            $model->addDynamicMethod('getTelegramFileId', function () use ($model) {
                return optional($model->tg)->file_id ?
                    $model->tg->file_id :
                    InputFile::create($model->path);
            });

            $model->addDynamicMethod('setTelegramFileId', function (Message $response) use ($model) {

                $photoObject = $response->getPhoto();

                if (!$photoObject) {
                    return;
                }

                $last = $photoObject->last();

                if (!isset($last)) {
                    return;
                }

                $file_id = $last['file_id'];

                if(optional($model->tg)->file_id){
                    return;
                }

                $telegramFile = new TelegramFile();
                $telegramFile->system_file_id = $model->id;
                $telegramFile->file_id = $file_id;
                $telegramFile->save();

            });
        });

        Customer::extend(function($model) {
            $model->hasOne['tg'] = [TelegramUser::class];
        });

        Product::extend(function($model) {
            $model->addDynamicMethod('getCaptionForTelegram', function() use ($model) {
                return "<b>" . $model->name . "</b>\n\n" . \Html::strip($model->description);
            });

            $model->addDynamicMethod('isVariant', function() use ($model) {
                return $model->inventory_management_method === 'variant';
            });
        });

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
        });



/*        Validator::extend('phoneUa', function($attribute, $value, $parameters) {
            $regex = "/^(((\+?)(38))\s?)?(([0-9]{3})|(\([0-9]{3}\)))(\-|\s)?(([0-9]{3})(\-|\s)?
        ([0-9]{2})(\-|\s)?([0-9]{2})|([0-9]{2})(\-|\s)?([0-9]{2})(\-|\s)?
        ([0-9]{3})|([0-9]{2})(\-|\s)?([0-9]{3})(\-|\s)?([0-9]{2}))$/";

            return preg_match($regex, $value);
        });*/



    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'EmojiSushiBot settings',
                'description' => 'Manage bot settings.',
                'category' => 'Telegram',
                'icon' => 'icon-cog',
                'class' => \Layerok\TgMall\Models\Settings::class,
                'order' => 500,
                'keywords' => 'telegram bot',
            ]
        ];
    }


}
