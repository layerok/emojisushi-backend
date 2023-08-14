<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddFieldToCitiesTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->boolean('is_main')->nullable();
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->dropColumn(['is_main',]);
        });
    }
}


