<?php

namespace Layerok\Restapi\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddCityToFcmTokensTable extends Migration
{
    public function up()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->string('city')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
}
