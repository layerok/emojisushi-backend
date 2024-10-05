<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('system_site_definitions', 'fallback_locale')) {
            Schema::table('system_site_definitions', function (Blueprint $table) {
                $table->string('fallback_locale')->nullable();
            });
        }
    }

    public function down()
    {
    }
};
