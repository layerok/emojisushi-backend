<?php namespace Layerok\BaseCode;

use Backend;
use Layerok\BaseCode\Events\TgMallOrderHandler;
use System\Classes\PluginBase;
use Event;
use Log;

/**
 * BaseCode Plugin Information File
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

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::subscribe(new TgMallOrderHandler());
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
