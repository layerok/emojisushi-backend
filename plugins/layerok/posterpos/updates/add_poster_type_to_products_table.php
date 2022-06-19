<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddPosterTypeToProductsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->string('poster_type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->dropColumn(['poster_type']);
        });
    }
}


