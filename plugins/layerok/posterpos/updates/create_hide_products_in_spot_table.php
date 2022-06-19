<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class CreateHideProductsInBranchTable extends Migration
{
    ///
    public function up()
    {
        Schema::create('layerok_posterpos_hide_products_in_spot', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('spot_id')->unsigned();
        });
    }

    public function down()
    {
        Schema::drop('layerok_posterpos_hide_products_in_spot');
    }
}


