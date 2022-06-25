<?php

namespace Layerok\TgMall\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateLayerokTgMallUsersTable2 extends Migration
{
    public function up()
    {
        Schema::table('layerok_tgmall_users', function (Blueprint $table) {
            $table->bigInteger('chat_id')->nullable()->change();
        });

    }

    public function down()
    {

        Schema::table('layerok_tgmall_users', function (Blueprint $table) {
            $table->integer('chat_id')->nullable()->change();
        });
    }
}
