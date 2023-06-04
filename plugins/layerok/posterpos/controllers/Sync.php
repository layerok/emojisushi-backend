<?php namespace Layerok\PosterPos\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Illuminate\Support\Collection;
use Layerok\PosterPos\Classes\Imports\ProductImport;
use Layerok\PosterPos\Classes\PosterTransition;
use Maatwebsite\Excel\Facades\Excel;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Observers\ProductObserver;
use OFFLINE\Mall\Models\Product;
use poster\src\PosterApi;
use Input;

/**
 * Spot Backend Controller
 */
class Sync extends Controller
{

    public function __construct()
    {
        ini_set('max_execution_time', 18000);
        set_time_limit(0);
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'posterpos', 'sync');
    }

    public function index()
    {

    }

    public function weight() {
        // get weight from poster and save it out database
        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];
        PosterApi::init($config);
        $products = (object)PosterApi::menu()->getProducts();


        foreach ($products->response as $value) {
            $product = Product::where('poster_id', '=', $value->product_id)->first();
            if (!$product) {
                // Если такого товара не существует, то выходим
                continue;
            }

            $product->update([
                'weight'  => (int)$value->out,
            ]);

            // Реиндексация
            $observer = new ProductObserver(app(Index::class));
            Product::where('id', '=', $product['id'])->orderBy('id')->with([
                'variants.prices.currency',
                'prices.currency',
                'property_values.property',
                'categories',
                'variants.prices.currency',
                'variants.property_values.property',
            ])->chunk(200, function (Collection $products) use ($observer) {
                $products->each(function (Product $product) use ($observer) {
                    $observer->updated($product);
                });
            });
        }

    }

    public function description_short() {


        $hasFile = Input::hasFile('file');

        if(!$hasFile) {
            return;
        }

        $file = Input::file('file');
        Excel::import(new ProductImport(), $file);


    }
}
