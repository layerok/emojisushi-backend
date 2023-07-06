<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class RemoveAddressIdFromSpotsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->dropColumn(['address_id']);
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->dropColumn(['address']);
            $table->integer('address_id');
        });
    }
}
