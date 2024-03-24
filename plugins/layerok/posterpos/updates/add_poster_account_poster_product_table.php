<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;
use \Layerok\PosterPos\Models\PosterAccount;
use \OFFLINE\Mall\Models\Product;

class AddPosterAccountPosterProductTable extends Migration
{
    public string $tableName = 'layerok_posterpos_poster_account_poster_product';

    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->unsignedInteger('product_id');
            $table->bigInteger('poster_account_id')->unsigned();
            $table->bigInteger('poster_product_id')->unsigned();

            $table->foreign('product_id', 'products_product_id')
                ->on('offline_mall_products')
                ->references('id')
                ->cascadeOnDelete();

            $table->foreign('poster_account_id', 'poster_accounts_account_id')
                ->on('layerok_posterpos_poster_accounts')
                ->references('id')
                ->cascadeOnDelete();
        });

        $products = Product::all();

        $kador = PosterAccount::where('account_name', 'emojisushikador')->first();
        $bazarna = PosterAccount::where('account_name', 'emoji-bar2')->first();

        $products->each(function($product) use($kador, $bazarna) {
            $relations = [];
            if($product->poster_id2) {
                $relations[$kador->id] = [
                    'poster_product_id' => $product->poster_id2,
                ];
            }

            if($product->poster_id) {
                $relations[$bazarna->id] = [
                    'poster_product_id' => $product->poster_id,
                ];
            }

            $product->poster_accounts()->sync($relations);
        });

        Schema::dropColumns('offline_mall_products', ['poster_id', 'poster_id2']);
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}


