<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddAvailabilityFields extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->boolean('temporarily_unavailable')->default(false);
        });

        Schema::table('layerok_posterpos_districts', function (Blueprint $table) {
            $table->boolean('temporarily_unavailable')->default(false);
        });

        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->boolean('temporarily_unavailable')->default(false);
        });


    }

    public function down()
    {
        Schema::table('layerok_posterpos_spots', function (Blueprint $table) {
            $table->dropColumn(['temporarily_unavailable']);
        });

        Schema::table('layerok_posterpos_districts', function (Blueprint $table) {
            $table->dropColumn(['temporarily_unavailable']);
        });

        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->dropColumn(['temporarily_unavailable']);
        });
    }
}
