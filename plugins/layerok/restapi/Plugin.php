<?php namespace Layerok\RestApi;

use Backend;
use OFFLINE\Mall\Classes\CategoryFilter\SortOrder\Bestseller;
use System\Classes\PluginBase;
use Config;
use Fruitcake\Cors\HandleCors;
use Fruitcake\Cors\CorsServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Event;

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

        Config::set('cors', Config::get('layerok.restapi::cors'));

        $this->app->register(CorsServiceProvider::class);

        $this->app[Kernel::class]->pushMiddleware(HandleCors::class);
    }


}
