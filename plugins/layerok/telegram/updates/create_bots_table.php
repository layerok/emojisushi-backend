<?php namespace Layerok\Telegram\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateBotsTable Migration
 */
class CreateBotsTable extends Migration
{
    public function up()
    {
        Schema::create('layerok_telegram_bots', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token');
            $table->string('name')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_telegram_bots');
    }


}
