<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->integer('site_id')->nullable()->unsigned();
            $table->integer('site_root_id')->nullable()->unsigned();
        });
    }

    public function down()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('site_id');
            $table->dropColumn('site_root_id');
        });
    }
};
