<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddCodeFieldToShippingMethodsTable extends Migration
{
    ///
    public function up()
    {
        Schema::table('offline_mall_shipping_methods', function (Blueprint $table) {
            $table->string('code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_shipping_methods', function (Blueprint $table) {
            $table->dropColumn(['code']);
        });
    }
}


