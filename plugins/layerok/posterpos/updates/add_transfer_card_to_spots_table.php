<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddTransferCardToSpotsTable extends Migration
{
    public string $tableName = 'layerok_posterpos_spots';

    public function up()
    {
        // Card number a courier shows to a client who pays by transfer.
        if (Schema::hasColumn($this->tableName, 'transfer_card')) {
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('transfer_card', 16)->nullable();
        });
    }

    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn('transfer_card');
        });
    }
}
