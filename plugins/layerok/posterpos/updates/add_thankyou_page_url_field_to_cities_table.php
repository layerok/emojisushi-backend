<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddThankYouPageUrlToCitiesTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->text('thankyou_page_url')->nullable();
        });
    }

    public function down()
    {
        Schema::table('layerok_posterpos_cities', function (Blueprint $table) {
            $table->dropColumn(['thankyou_page_url']);
        });
    }
}


