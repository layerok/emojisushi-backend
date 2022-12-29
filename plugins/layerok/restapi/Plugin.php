<?php namespace Layerok\RestApi;

use Layerok\RestApi\Classes\Customer\DefaultSignUpHandler;
use OFFLINE\Mall\Classes\Customer\SignUpHandler;
use System\Classes\PluginBase;
use Config;
use Fruitcake\Cors\HandleCors;
use Fruitcake\Cors\CorsServiceProvider;
use Illuminate\Contracts\Http\Kernel;


/**
 * RestApi Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['OFFLINE.Mall', 'Layerok.BaseCode'];

    public function pluginDetails()
    {
        return [
            'name'        => 'RestApi',
            'description' => 'No description provided yet...',
            'author'      => 'Layerok',
            'icon'        => 'icon-leaf'
        ];
    }

    public function register()
    {

    }

    public function boot()
    {
/*        Event::listen('offline.mall.extendSortOrder', function() {
            return ['default' => new Bestseller()];
        });*/

        $this->app->bind(SignUpHandler::class, function () {
            return new DefaultSignUpHandler();
        });

        Config::set('cors', Config::get('layerok.restapi::cors'));

        $this->app->register(CorsServiceProvider::class);

        $this->app[Kernel::class]->pushMiddleware(HandleCors::class);
    }


}
