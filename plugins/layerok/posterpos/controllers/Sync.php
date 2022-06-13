<?php namespace Layerok\PosterPos\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Illuminate\Support\Collection;
use Layerok\PosterPos\Classes\PosterTransition;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Observers\ProductObserver;
use OFFLINE\Mall\Models\Product;
use poster\src\PosterApi;

/**
 * Spot Backend Controller
 */
class Sync extends Controller
{

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'posterpos', 'sync');
    }

    public function index()
    {

    }

    public function weight() {
        PosterApi::init();
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

            \Log::info($product->id . " " . (int)$value->out);

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
}
