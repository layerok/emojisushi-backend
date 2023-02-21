<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddGoogleMapUrlFieldToSpotsTable extends Migration
{
    ///
    public function up()
    {
        if (!Schema::hasColumn('layerok_posterpos_spots', 'google_map_url')) {
            Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
                $table->string('google_map_url')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->dropColumn(['google_map_url']);
        });
    }
}


