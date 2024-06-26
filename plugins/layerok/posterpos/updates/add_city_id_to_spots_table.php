<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddCityIdToSpotsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->integer('city_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->dropColumn(['city_id']);
        });
    }
}
