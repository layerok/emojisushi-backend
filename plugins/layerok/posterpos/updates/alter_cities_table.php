<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AlterCitiesTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->string('frontend_url')->nullable();
            $table->string('google_map_url')->nullable();
            $table->string('phones')->nullable();
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->dropColumn([
                'frontend_url',
                'google_map_url',
                'phones'
            ]);
        });
    }
}


