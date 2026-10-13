<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddDeliveryEventsTables extends Migration
{
    public function up()
    {
        // Last state of each delivery receipt as seen by the Poster webhook. Poster
        // only says "this receipt changed", so the event is the difference between
        // this row and what the receipt looks like now.
        Schema::create('layerok_posterpos_delivery_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transaction_id')->unique();
            $table->unsignedBigInteger('spot_poster_id')->nullable()->index();
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->unsignedSmallInteger('processing_status')->nullable();
            $table->timestamps();
        });

        Schema::create('layerok_posterpos_delivery_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 40);
            $table->unsignedBigInteger('transaction_id')->nullable()->index();
            $table->unsignedBigInteger('incoming_order_id')->nullable()->index();
            $table->unsignedBigInteger('spot_poster_id')->nullable()->index();
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->unsignedBigInteger('previous_courier_id')->nullable();
            $table->unsignedSmallInteger('processing_status')->nullable();
            $table->string('address')->nullable();
            $table->string('client')->nullable();
            $table->timestamp('happened_at')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_delivery_events');
        Schema::dropIfExists('layerok_posterpos_delivery_snapshots');
    }
}
