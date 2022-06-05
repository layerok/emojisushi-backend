<?php namespace Layerok\PosterPos;

use Backend;
use Illuminate\Support\Facades\Event;
use OFFLINE\Mall\Controllers\Categories;
use OFFLINE\Mall\Controllers\Products;
use OFFLINE\Mall\Controllers\Variants;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;
use System\Classes\PluginBase;

/**
 * PosterPos Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['OFFLINE.Mall', 'Layerok.Telegram'];
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'PosterPos',
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
        $this->registerConsoleCommand('posterpos.import', \Layerok\PosterPos\Console\ImportData::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::listen('backend.form.extendFields', function ($widget) {

            if (!$widget->getController() instanceof Categories &&
                !$widget->getController() instanceof Products  &&
                !$widget->getController() instanceof Variants) {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof Category &&
                !$widget->model instanceof Product  &&
                !$widget->model instanceof Variant) {
                return;
            }

            // Add an extra birthday field
            $widget->addFields([
                'poster_id' => [
                    'label'   => 'offline.mall::lang.extend.poster_id',
                    'span' => 'left',
                    'type' => 'text'
                ]
            ]);

            if ($widget->model instanceof Category) {
                $widget->addFields([
                    'published' => [
                        'label' => 'offline.mall::lang.extend.published',
                        'span' => 'left',
                        'type' => 'switch'
                    ]
                ]);
            }
        });

        // Extend all backend list usage
        Event::listen('backend.list.extendColumns', function ($widget) {

            if (!$widget->getController() instanceof Categories &&
                !$widget->getController() instanceof Products  &&
                !$widget->getController() instanceof Variants) {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof Category &&
                !$widget->model instanceof Product  &&
                !$widget->model instanceof Variant) {
                return;
            }

            $widget->addColumns([
                'poster_id' => [
                    'label' => 'layerok.posterpos::lang.extend.poster_id'
                ]
            ]);

            if ($widget->model instanceof Category &&
                $widget->getController() instanceof Categories) {
                $widget->addColumns([
                    'published' => [
                        'label' => 'layerok.posterpos::lang.extend.published',
                        'type' => 'partial',
                        'path' => '$/offline/mall/models/product/_published.htm',
                        'sortable' => true
                    ]
                ]);
            }

        });

        Category::extend(function($model){
            $model->fillable[] = 'poster_id';
            $model->fillable[] = 'published';

            $model->casts['published'] = 'boolean';
            $model->rules['published'] = 'boolean';
        });

        Product::extend(function($model){
            $model->fillable[] = 'poster_id';
        });
    }



    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'posterpos' => [
                'label'       => 'PosterPos',
                'url'         => Backend::url('layerok/posterpos/mycontroller'),
                'icon'        => 'icon-shopping-bag',
                'permissions' => ['layerok.posterpos.*'],
                'order'       => 500,
                'sideMenu' => [
                    'posterpos-spots' => [
                        'label' => "Spots",
                        'icon'   => 'icon-globe',
                        'url'    => Backend::url('layerok/posterpos/spot'),
                    ],
                    'posterpos-tablets' => [
                        'label' => "Tablets",
                        'icon'   => 'icon-tablet',
                        'url'    => Backend::url('layerok/posterpos/tablet'),
                    ],
                ]
            ],
        ];
    }


}
