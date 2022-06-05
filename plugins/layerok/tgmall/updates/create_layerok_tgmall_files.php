<?php

namespace Layerok\TgMall\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateLayerokTgMallFiles extends Migration
{
    public function up()
    {
        Schema::create('layerok_tgmall_files', function ($table) {
            $table->increments('id');
            $table->integer('system_file_id');
            $table->string('file_id')->nullable();
        });
    }

    public function down()
    {
        Schema::drop('layerok_tgmall_files');
    }
}
