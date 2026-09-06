<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddKbzhuToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->decimal('calories', 8, 2)->nullable();
            $table->decimal('proteins', 8, 2)->nullable();
            $table->decimal('fats', 8, 2)->nullable();
            $table->decimal('carbs', 8, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->dropColumn(['calories', 'proteins', 'fats', 'carbs']);
        });
    }
}
