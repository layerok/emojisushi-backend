<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddAddressIdToSpotsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->integer('address_id')->nullable();
            $table->dropColumn(['address']);
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            if (Schema::hasColumn('offline_mall_product_variants', 'poster_id')) {
                $table->dropColumn(['address_id']);
            }
            $table->string('address')->nullable();
        });
    }
}
