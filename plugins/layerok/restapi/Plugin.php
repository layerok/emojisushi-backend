<?php namespace Layerok\RestApi;

use Backend;
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
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'RestApi',
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

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        Config::set('cors', Config::get('layerok.restapi::cors'));

        $this->app->register(CorsServiceProvider::class);

        $this->app[Kernel::class]->pushMiddleware(HandleCors::class);
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
            'Layerok\RestApi\Components\MyComponent' => 'myComponent',
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
            'layerok.restapi.some_permission' => [
                'tab' => 'RestApi',
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
            'restapi' => [
                'label'       => 'RestApi',
                'url'         => Backend::url('layerok/restapi/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['layerok.restapi.*'],
                'order'       => 500,
            ],
        ];
    }
}
