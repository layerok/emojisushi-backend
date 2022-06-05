<?php namespace Layerok\PosterPos\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateTabletsTable Migration
 */
class CreateTabletsTable extends Migration
{
    public function up()
    {
        Schema::create('layerok_posterpos_tablets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('spot_id')->unsigned();
            $table->integer('tablet_id')->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_tablets');
    }
}
