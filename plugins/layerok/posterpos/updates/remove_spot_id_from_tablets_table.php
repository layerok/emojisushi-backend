<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class RemoveSpotIdFromTabletsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_tablets', function (Blueprint $table) {
            $table->dropColumn(['spot_id',]);
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_tablets', function (Blueprint $table) {
            $table->integer('spot_id')->unsigned();
        });
    }
}


