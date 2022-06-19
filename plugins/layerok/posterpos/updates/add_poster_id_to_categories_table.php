<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddPosterIdToCategoriesTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('offline_mall_categories', function (Blueprint $table) {
            $table->integer('poster_id')->nullable();
            $table->boolean('published')->default(true);
        });
    }

    public function down()
    {
        Schema::table('offline_mall_categories', function (Blueprint $table) {
            if (Schema::hasColumn('offline_mall_categories', 'poster_id')) {
                $table->dropColumn(['poster_id']);
            }
            if (Schema::hasColumn('offline_mall_categories', 'published')) {
                $table->dropColumn(['published']);
            }
        });
    }
}


