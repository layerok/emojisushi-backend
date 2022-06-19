<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class CreateBranchesTable extends Migration
{
    ///
    public function up()
    {
        Schema::create('layerok_posterpos_spots', function ($table) {
            $table->increments('id')->index();
            $table->string('name')->nullable();
            $table->integer('bot_id')->unsigned()->nullable();
            $table->integer('chat_id')->unsigned()->nullable();
            $table->string('code')->nullable();
            $table->text('phones')->nullable();
            $table->string('address')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('layerok_posterpos_spots');
    }
}


