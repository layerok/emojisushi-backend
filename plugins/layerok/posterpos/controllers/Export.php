<?php
namespace Layerok\PosterPos\Controllers;

use Artisan;
use BackendMenu;
use Layerok\PosterPos\Classes\Exports\PosterCategoriesExport;
use Layerok\PosterPos\Classes\Exports\PosterDishesExport;
use Layerok\PosterPos\Classes\Exports\PosterIngredientsExport;
use Layerok\PosterPos\Classes\Exports\PosterProductsExport;
use Maatwebsite\Excel\Facades\Excel;


class Export extends \Backend\Classes\Controller
{
    public function __construct() {
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'export');
    }

    public function index()
    {

    }

    public function items() {
        $map = [
            'product' => PosterProductsExport::class,
            'category' => PosterCategoriesExport::class,
            'ingredient' => PosterIngredientsExport::class,
            'dish' => PosterDishesExport::class,
        ];
        $type = input('type');
        $exist = in_array($type, array_keys($map));
        if($type && $exist) {
            $class = $map[$type];
            return Excel::download(new $class(), 'poster_' . $type . date('Y-m-d_h-m-s') .'.xlsx');
        }

    }

    public function latest() {
        ini_set('max_execution_time', 18000);
        set_time_limit(0);
        Artisan::call('poster:import-products', ['--force' => true, '--reindex' => true]);
    }
}
