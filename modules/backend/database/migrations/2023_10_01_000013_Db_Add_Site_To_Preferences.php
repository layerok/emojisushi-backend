<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('backend_user_preferences', function (Blueprint $table) {
            $table->integer('site_id')->nullable()->unsigned();
            $table->integer('site_root_id')->nullable()->unsigned();
        });
    }

    public function down()
    {
        Schema::table('backend_user_preferences', function (Blueprint $table) {
            $table->dropColumn('site_id');
            $table->dropColumn('site_root_id');
        });
    }
};
