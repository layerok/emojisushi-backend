<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateLayerokPosterPosTabletsTable extends Migration
{
    public string $table = 'layerok_posterpos_tablets';

    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->bigInteger('poster_account_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn(['poster_account_id']);
        });
    }
};


