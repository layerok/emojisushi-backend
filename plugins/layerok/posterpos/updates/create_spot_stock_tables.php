<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateSpotStockTables extends Migration
{
    public function up()
    {
        // A limited amount of a product at one spot. No row means unlimited.
        Schema::create('layerok_posterpos_spot_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('spot_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('amount')->unsigned()->default(0);
            $table->timestamps();

            $table->unique(['spot_id', 'product_id']);
        });

        // What each order took from stock, so a payment that fails or an order
        // cancelled in Poster can put exactly that back, and only once.
        Schema::create('layerok_posterpos_stock_reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('spot_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('quantity')->unsigned();
            $table->bigInteger('online_order_id')->unsigned()->nullable()->index();
            $table->bigInteger('incoming_order_id')->unsigned()->nullable()->index();
            $table->string('status', 16)->default('reserved');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_stock_reservations');
        Schema::dropIfExists('layerok_posterpos_spot_stocks');
    }
}
