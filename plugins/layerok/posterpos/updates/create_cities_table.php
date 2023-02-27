<?php namespace Layerok\PosterPos\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateTabletsTable Migration
 */
class CreateCitiesTable extends Migration
{
    public function up()
    {
        Schema::create('layerok_posterpos_cities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug', 191);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('layerok_posterpos_cities');
    }
}
