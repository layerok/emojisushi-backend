<?php namespace Layerok\Telegram\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateChatsTable Migration
 */
class CreateChatsTable extends Migration
{
    public function up()
    {
        Schema::create('layerok_telegram_chats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('internal_id');
            $table->string('name')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_telegram_chats');
    }
}
