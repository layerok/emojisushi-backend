<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('backend_user_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('backend_user_roles', 'sort_order')) {
                $table->integer('sort_order')->nullable();
            }

            if (!Schema::hasColumn('backend_user_roles', 'color_background')) {
                $table->string('color_background')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('backend_user_roles', function (Blueprint $table) {
            if (Schema::hasColumn('backend_user_roles', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            if (Schema::hasColumn('backend_user_roles', 'color_background')) {
                $table->dropColumn('color_background');
            }
        });
    }
};
