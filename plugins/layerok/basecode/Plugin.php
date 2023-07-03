<?php namespace Layerok\BaseCode;

use Backend;
use System\Classes\PluginBase;
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
