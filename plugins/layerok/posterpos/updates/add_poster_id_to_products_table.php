<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddPosterIdToProductsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->integer('poster_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            if (Schema::hasColumn('offline_mall_products', 'poster_id')) {
                $table->dropColumn(['poster_id']);
            }
        });
    }
}


