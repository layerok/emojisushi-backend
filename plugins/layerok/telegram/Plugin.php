<?php namespace Layerok\Telegram;

use Backend;
use Layerok\Telegram\Console\ImportData;
use System\Classes\PluginBase;

/**
 * Telegram Plugin Information File
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
            'name'        => 'Telegram',
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
        $this->registerConsoleCommand('telegram.seed-test', ImportData::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {

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
            'Layerok\Telegram\Components\MyComponent' => 'myComponent',
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
            'layerok.telegram.some_permission' => [
                'tab' => 'Telegram',
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

        return [
            'telegram' => [
                'label'       => 'Telegram',
                'url'         => Backend::url('layerok/telegram/bot'),
                'icon'        => 'icon-paper-plane',
                'permissions' => ['layerok.telegram.*'],
                'order'       => 500,
                'sideMenu' => [
                    'telegram-bots' => [
                        'label' => 'Bots',
                        'url' => Backend::url('layerok/telegram/bot'),
                        'icon' => 'icon-android'
                    ],
                    'telegram-chats' => [
                        'label' => 'Chats',
                        'url' => Backend::url('layerok/telegram/chat'),
                        'icon' => 'icon-commenting'
                    ],
                ]
            ],
        ];
    }

}
