<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * some_upgrade_file.php
 */
class AddLayerokPosterPosPosterTable extends Migration
{
    public string $accountTableName = 'layerok_posterpos_poster_accounts';
    public string $spotTableName = 'layerok_posterpos_spots';

    public function up()
    {
        Schema::create($this->accountTableName, function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('account_name')->nullable();
            $table->string('url')->nullable();
            $table->string('application_secret')->nullable();
            $table->string('application_id')->nullable();
            $table->timestamps();
        });

        Schema::table($this->spotTableName, function (Blueprint $table) {
            $table->bigInteger('poster_account_id')->nullable();
        });
    }

    public function down()
    {
        Schema::drop($this->accountTableName);

        Schema::table($this->spotTableName, function (Blueprint $table) {
            $table->dropColumn(['poster_account_id']);
        });
    }
}


