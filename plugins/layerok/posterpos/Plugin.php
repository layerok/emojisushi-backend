<?php namespace Layerok\PosterPos;

use Backend;
use File;
use Illuminate\Support\Facades\Event;
use Layerok\PosterPos\Classes\Customer\AuthManager;
use Layerok\PosterPos\Console\ImportCategories;
use Layerok\PosterPos\Console\CreatePaymentMethods;
use Layerok\PosterPos\Console\CreateShippingMethods;
use Layerok\PosterPos\Console\CreateUAHCurrency;
use Layerok\PosterPos\Console\ImportData;
use Layerok\PosterPos\Console\ImportIngredients;
use Layerok\PosterPos\Console\ImportProducts;
use Layerok\PosterPos\Console\ImportSpots;
use Layerok\PosterPos\Console\ImportTablets;
use Layerok\PosterPos\Models\Cart;
use Layerok\PosterPos\Models\Spot;
use Layerok\PosterPos\Models\Wishlist;
use Maatwebsite\Excel\ExcelServiceProvider;
use Maatwebsite\Excel\Facades\Excel;
use OFFLINE\Mall\Controllers\Categories;
use OFFLINE\Mall\Controllers\Products;
use OFFLINE\Mall\Controllers\Variants;

use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Order;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\ShippingMethod;
use OFFLINE\Mall\Models\Variant;

use System\Classes\PluginBase;
use App;

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
        $this->registerConsoleCommand('poster.import', ImportData::class);
        $this->registerConsoleCommand('poster.import-products', ImportProducts::class);
        $this->registerConsoleCommand('poster.import-spots', ImportSpots::class);
        $this->registerConsoleCommand('poster.import-tablets', ImportTablets::class);
        $this->registerConsoleCommand('poster.import-categories', ImportCategories::class);
        $this->registerConsoleCommand('poster.import-ingredients', ImportIngredients::class);
        $this->registerConsoleCommand('poster.create-uah-currency', CreateUAHCurrency::class);
        $this->registerConsoleCommand('poster.create-payment-methods', CreatePaymentMethods::class);
        $this->registerConsoleCommand('poster.create-shipping-methods', CreateShippingMethods::class);
        App::register(ExcelServiceProvider::class);
        App::registerClassAlias('Excel',  Excel::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

        // Use custom user model
        App::singleton('user.auth', function () {
            return AuthManager::instance();
        });

        Event::listen('system.extendConfigFile', function ( $path, $config) {


            if ($path === '/plugins/offline/mall/models/property/fields_pivot.yaml') {
                $config['fields']['options']['form']['fields']['poster_id'] = [
                    'label' => 'Poster ID',
                    'type' => 'text',
                    'span' => 'left'
                ];
                $config['fields']['options']['form']['fields']['value']['span'] = 'right';
                return $config;
            }

            if ($path === '/plugins/offline/mall/models/propertygroup/fields.yaml') {
                $config['fields']['poster_id'] = [
                    'label' => 'Poster ID',
                    'type' => 'text',
                    'span' => 'auto'
                ];

                return $config;
            }




            if ($path === '/plugins/offline/mall/models/category/fields.yaml') {
                $config['fields']['hide_categories_in_spot'] = [
                    'label' => 'Скрыть категорию в заведении',
                    'type' => 'relation',
                ];
                return $config;
            }

            if ($path === '/plugins/offline/mall/models/shippingmethod/fields.yaml') {
                $config['fields']['code'] = [
                    'label' => 'Code',
                    'span' => 'auto'
                ];
                return $config;
            }

            if ($path === '/plugins/offline/mall/models/shippingmethod/columns.yaml') {
                $config['columns']['code'] = [
                    'label' => 'Code',
                ];
                return $config;
            }


            if ($path === '/plugins/offline/mall/models/product/columns.yaml' ||
                $path === '/plugins/offline/mall/models/product/fields_create.yaml' ||
                $path === '/plugins/offline/mall/models/product/fields_edit.yaml') {

                if($path === '/plugins/offline/mall/models/product/fields_edit.yaml') {
                    $config['tabs']['fields']['hide_products_in_spot'] = [
                        'type' => 'relation',
                        'tab' => 'offline.mall::lang.product.general'
                    ];
                }
                $config['fields']['poster_id'] = [
                    'label'   => 'layerok.posterpos::lang.extend.poster_id',
                    'span' => 'left',
                    'type' => 'text'
                ];
                return $config;
            }


        });
        Event::listen('backend.form.extendFields', function ($widget) {

            if (!$widget->getController() instanceof Categories &&
                !$widget->getController() instanceof Variants) {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof Category &&
                !$widget->model instanceof Variant) {
                return;
            }

            // Add an extra birthday field
            $widget->addFields([
                'poster_id' => [
                    'label'   => 'layerok.posterpos::lang.extend.poster_id',
                    'span' => 'left',
                    'type' => 'text'
                ]
            ]);

            if ($widget->model instanceof Category) {
                $widget->addFields([
                    'published' => [
                        'label' => 'layerok.posterpos::lang.extend.published',
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


        Event::listen('backend.page.beforeDisplay', function ( $backendController, $action, $params) {
            // workaround, trick controller to look for the template outside the self plugin folder
            if($backendController instanceof Products && $action === 'export') {
                $backendController->addViewPath(File::normalizePath("plugins\\layerok\\posterpos\\controllers\\products"));
            }
       });

        ShippingMethod::extend((function($model) {
            $model->fillable[] = 'code';
        }));

        Category::extend(function($model){
            $model->fillable[] = 'poster_id';
            $model->fillable[] = 'published';

            $model->casts['published'] = 'boolean';
            $model->rules['published'] = 'boolean';

            $model->belongsToMany['hide_categories_in_spot'] = [
                Spot::class,
                'table'    => 'layerok_posterpos_hide_categories_in_spot',
                'key'      => 'category_id',
                'otherKey' => 'spot_id',
            ];
        });

        Products::extend(function($controller) {
           $controller->addDynamicProperty('importExportConfig', 'plugins/layerok/posterpos/models/product/config_import_export.yaml');
           $controller->implement[] =  \Backend\Behaviors\ImportExportController::class;
        });

        Product::extend(function($model){
            $model->fillable[] = 'poster_id';
            $model->belongsToMany['hide_products_in_spot'] = [
                Spot::class,
                'table'    => 'layerok_posterpos_hide_products_in_spot',
                'key'      => 'product_id',
                'otherKey' => 'spot_id',
            ];
        });

        Variant::extend(function($model){
            $model->addFillable('poster_id');
        });

        Property::extend(function($model){
            $model->fillable[] = 'poster_id';
            $model->fillable[] = 'poster_type'; // dish or product
        });

        PropertyGroup::extend(function($model){
            $model->fillable[] = 'poster_id';
        });



        Cart::extend(function ($model) {
            $model->fillable[] = 'spot_id';
            $model->hasOne['spot'] = Spot::class;
        });

        Order::extend(function ($model) {
            $model->hasOne['spot'] = Spot::class;
        });

        Wishlist::extend(function ($model) {
            $model->fillable[] = 'spot_id';
            $model->hasOne['spot'] = Spot::class;
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
                        'icon'   => 'icon-map-marker',
                        'url'    => Backend::url('layerok/posterpos/spot'),
                    ],
                    'posterpos-cities' => [
                        'label' => "Cities",
                        'icon'   => 'icon-globe',
                        'url'    => Backend::url('layerok/posterpos/cities'),
                    ],
                    'posterpos-tablets' => [
                        'label' => "Tablets",
                        'icon'   => 'icon-tablet',
                        'url'    => Backend::url('layerok/posterpos/tablet'),
                    ],
                    'posterpos-export' => [
                        'label' => 'Export',
                        'icon'   => 'icon-download',
                        'url' => Backend::url('layerok/posterpos/export')
                    ],
                    'posterpos-import' => [
                        'label' => 'Import',
                        'icon' => 'icon-upload',
                        'url' => Backend::url('layerok/posterpos/import')
                    ],
                    'posterpos-sync' => [
                        'label' => 'Sync',
                        'icon' => 'icon-upload',
                        'url' => Backend::url('layerok/posterpos/sync')
                    ]
                ]
            ],
        ];
    }


}
