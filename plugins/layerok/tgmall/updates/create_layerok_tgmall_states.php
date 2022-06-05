<?php

namespace Layerok\TgMall\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateLayerokTgMallStates extends Migration
{
    public function up()
    {
        Schema::create('layerok_tgmall_states', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->json('state')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('layerok_tgmall_states');
    }
}
