<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddClientUuidToCourierTasks extends Migration
{
    public string $tableName = 'layerok_posterpos_courier_tasks';

    public function up()
    {
        // The courier app retries a request until it lands, so the same extra
        // trip can arrive twice. The app gives each one an id; a repeat with the
        // same id returns the existing task instead of adding a second.
        if (Schema::hasColumn($this->tableName, 'client_uuid')) {
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('client_uuid', 36)->nullable()->unique('lp_courier_tasks_client_uuid_uq');
        });
    }

    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique('lp_courier_tasks_client_uuid_uq');
            $table->dropColumn('client_uuid');
        });
    }
}
