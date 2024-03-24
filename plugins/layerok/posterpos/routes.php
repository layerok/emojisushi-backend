<?php

use Illuminate\Support\Facades\Route;
use Layerok\PosterPos\Controllers\PosterWebhookController;
use Layerok\PosterPos\Controllers\WayForPayController;
use Layerok\PosterPos\Models\PosterAccount;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\Variant;

Route::post('/posterpos/webhook/handle', PosterWebhookController::class);
Route::post('/wayforpay-service-url', WayForPayController::class);

Route::get('test', function () {
    return explode('.', request()->header('host'))[0];
});

Route::get('migrate', function () {
    $kador = PosterAccount::where('account_name', 'emojisushikador')->first();
    $bazarna = PosterAccount::where('account_name', 'emoji-bar2')->first();

    // migrate models
    $records = collect([
        Property::all(),
        PropertyGroup::all(),
        Variant::all(),
        Category::all(),
        Product::all()
    ])->flatten(2);

    $records->each(function($record) use($bazarna, $kador) {
        $relations = [];

        if(!empty($record->poster_id) && !empty($bazarna)) {
            $relations[$bazarna->id] = [
                'poster_id' => $record->poster_id,
            ];
        }

        if(!empty($record->poster_id2) && !empty($kador)) {
            $relations[$kador->id] = [
                'poster_id' => $record->poster_id2,
            ];
        }

        $record->poster_accounts()->sync($relations);
    });
    return '';
});
