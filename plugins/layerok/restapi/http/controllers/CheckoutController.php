<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Classes\SpotStockService;
use Layerok\PosterPos\Models\ShippingMethod;
use Layerok\PosterPos\Models\Spot;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\Product;

class CheckoutController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'payment_methods' => $this->getPaymentMethods(),
            'shipping_methods' => $this->getShippingMethods(),
            'spots' => $this->getSpots(),
        ]);
    }

    public function getPaymentMethods(): array
    {
        $records = PaymentMethod::where('is_enabled', 1)->get();

        return $records->toArray();
    }

    public function getShippingMethods(): array
    {
        $records =  ShippingMethod::all();

        return $records->toArray();
    }

    public function getSpots(): array
    {
        $spots = Spot::with('city', 'unavailable_categories', 'unavailable_products', 'recommended_products')
            ->where('published', 1)
            ->get();

        $limitsBySpot = (new SpotStockService())->limitsBySpot();

        return $spots->map(function ($spot) use ($limitsBySpot) {
            $data = $spot->toArray();
            $limits = $limitsBySpot[$spot->id] ?? [];

            // Limited products that ran out are unavailable just like ones switched off.
            $data['unavailable_products'] = array_values(array_unique(array_merge(
                $spot->unavailable_products->pluck('id')->toArray(),
                SpotStockService::soldOut($limits)
            )));
            $data['limited_products'] = $limits;
            $productIds = $spot->recommended_products->pluck('id')->toArray();

            $unorderedProducts = Product::with([
                'variants',
                'variants.property_values',
                'hide_products_in_spot',
                'categories.hide_categories_in_spot',
                'variants.additional_prices',
                'image_sets',
                'prices',
                'additional_prices',
                'property_values' => function ($query) {
                    $query->where('value', '!=', '0');
                }
            ])->find($productIds);
            $data['recommended_products'] = $unorderedProducts;


            return $data;
        })->toArray();
    }
}
