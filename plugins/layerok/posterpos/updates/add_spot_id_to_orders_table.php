<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddSpotIdToOrdersTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('offline_mall_orders', function (Blueprint $table) {
            $table->integer('spot_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_orders', function (Blueprint $table) {
            $table->dropColumn(['spot_id']);

        });
    }
}


