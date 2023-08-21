<?php namespace Layerok\PosterPos\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Layerok\PosterPos\Classes\PosterTransition;
use OFFLINE\Mall\Models\Product;
use poster\src\PosterApi;

/**
 * Diagnostics Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Diagnostics extends Controller
{


    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['layerok.posterpos.diagnostics'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'posterpos', 'diagnostics');
    }

    public function index() {
        PosterApi::init(config('poster'));
        $posterProducts = PosterApi::menu()->getProducts()->response;
        $siteProducts = Product::all();

        $poster_ids = array_map(function($posterProduct) {
            return $posterProduct->product_id;
        }, $posterProducts);

        $site_product_ids = $siteProducts->map(function($siteProduct) {
            return $siteProduct->poster_id;
        })->toArray();

        $stale_products = [];
        foreach($siteProducts as $siteProduct) {
            if(!in_array($siteProduct->poster_id, $poster_ids)) {
                $stale_products[] = $siteProduct;
            }
        }

        $missing_products = [];

        foreach($posterProducts as $posterProduct) {
            if(!in_array($posterProduct->product_id, $site_product_ids)) {
                $missing_products[] = $posterProduct;
            }
        }

        $disconnected_products = $siteProducts->filter(function(Product $product) {
            return !$product->poster_id;
        });



        $this->vars['stale_products'] = $stale_products;
        $this->vars['missing_products'] = $missing_products;
        $this->vars['disconnected_products'] = $disconnected_products;
    }

    public function add() {
        $id = input('poster_id');
        PosterApi::init(config('poster'));
        $product = PosterApi::menu()->getProduct([
            'product_id' => $id
        ])->response;
        $transition = new PosterTransition;
        $transition->createProduct($product);
        return redirect('/backend/layerok/posterpos/diagnostics/index');
    }
}
