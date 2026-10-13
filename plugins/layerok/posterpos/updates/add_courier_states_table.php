<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddCourierStatesTable extends Migration
{
    public function up()
    {
        // MySQL commits each CREATE on its own; clear what a failed earlier run
        // of this same migration may have left behind.
        Schema::dropIfExists('layerok_posterpos_courier_states');

        // What the courier app reports that Poster can't hold: the order being
        // delivered right now («Везу сейчас») and whether the courier is on the
        // way back to the spot. One row per courier, overwritten on each report.
        Schema::create('layerok_posterpos_courier_states', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('courier_id')->unique('lp_courier_states_courier_uq');
            $table->unsignedBigInteger('current_transaction_id')->nullable()->index('lp_courier_states_current_idx');
            $table->boolean('returning')->default(false);
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_courier_states');
    }
}
