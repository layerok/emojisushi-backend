<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddSlightlySpicyToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->boolean('slightly_spicy')->nullable()->default(false);
        });
    }

    public function down()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->dropColumn(['slightly_spicy']);
        });
    }
}
