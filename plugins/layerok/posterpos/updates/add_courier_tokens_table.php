<?php

namespace Layerok\PosterPos\Updates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddCourierTokensTable extends Migration
{
    public function up()
    {
        // MySQL commits each CREATE on its own; clear what a failed earlier run
        // of this same migration may have left behind.
        Schema::dropIfExists('layerok_posterpos_courier_tokens');

        // Courier app sessions. Only a SHA-256 of each token is stored, so a
        // leaked table cannot be replayed as logins.
        Schema::create('layerok_posterpos_courier_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('courier_id')->index('lp_courier_tokens_courier_idx');
            $table->char('token_hash', 64)->unique('lp_courier_tokens_hash_uq');
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Courier passwords were stored as typed. Hash them in place so the
        // couriers' existing logins keep working.
        DB::table('layerok_posterpos_couriers')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->orderBy('id')
            ->get(['id', 'password'])
            ->each(function ($row) {
                if (Hash::info($row->password)['algoName'] !== 'unknown') {
                    return; // already a hash
                }

                DB::table('layerok_posterpos_couriers')
                    ->where('id', $row->id)
                    ->update(['password' => Hash::make($row->password)]);
            });
    }

    public function down()
    {
        // Hashed passwords cannot be turned back into the originals; couriers
        // keep their hashes and the app keeps verifying them.
        Schema::dropIfExists('layerok_posterpos_courier_tokens');
    }
}
