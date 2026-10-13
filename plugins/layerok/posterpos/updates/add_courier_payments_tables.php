<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddCourierPaymentsTables extends Migration
{
    public function up()
    {
        // MySQL commits each CREATE on its own, so a run that failed half-way
        // leaves some of these tables behind while the version stays unrecorded,
        // and the retry stops at "table already exists". Nothing but this
        // migration creates them, so clearing leftovers is safe.
        $this->down();

        // Jobs a courier does that are not Poster receipts — a top-up delivery, a
        // purchase, a trip to the shop. Each one is paid as one delivery.
        Schema::create('layerok_posterpos_courier_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Index names are given explicitly: the generated ones are built from
            // the long table name and can exceed MySQL's 64-character limit.
            $table->unsignedBigInteger('courier_id')->index('lp_courier_tasks_courier_idx');
            $table->unsignedBigInteger('spot_id')->nullable();
            $table->string('type', 20);
            $table->string('details')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('performed_at')->index('lp_courier_tasks_performed_idx');
            // 'app' once couriers enter these themselves, 'admin' for corrections.
            $table->string('source', 10)->default('admin');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // One courier's pay for one day. Km is typed in while the day is open;
        // closing freezes the numbers and the item list, so a receipt edited in
        // Poster afterwards cannot change what was already paid.
        Schema::create('layerok_posterpos_courier_shifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->unsignedBigInteger('courier_id');
            $table->decimal('km', 8, 2)->default(0);
            $table->decimal('rate_per_km', 8, 2)->nullable();
            $table->decimal('price_per_delivery', 8, 2)->nullable();
            $table->unsignedInteger('receipts_count')->default(0);
            $table->unsignedInteger('tasks_count')->default(0);
            $table->decimal('total', 10, 2)->nullable();
            $table->mediumText('items')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('closed_by')->nullable();
            $table->timestamps();

            $table->unique(['date', 'courier_id'], 'lp_courier_shifts_date_courier_uq');
        });

        // Poster receipts left out of a courier's pay. They cannot be deleted in
        // Poster from here, so "delete" on the payment page means this.
        Schema::create('layerok_posterpos_courier_excluded_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transaction_id')->unique('lp_courier_excluded_tx_uq');
            $table->date('date')->index('lp_courier_excluded_date_idx');
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->unsignedInteger('excluded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_courier_excluded_receipts');
        Schema::dropIfExists('layerok_posterpos_courier_shifts');
        Schema::dropIfExists('layerok_posterpos_courier_tasks');
    }
}
