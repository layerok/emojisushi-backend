<?php namespace Layerok\PosterPos\Updates;

use Artisan;
use DB;
use October\Rain\Database\Updates\Migration;
use Schema;


class CreateChatSpotTable extends Migration
{
    public function up()
    {
        Schema::create('layerok_posterpos_chat_spot', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('spot_id')->unsigned();
            $table->integer('chat_id')->unsigned();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_chat_spot');
    }
}
