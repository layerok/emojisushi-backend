<?php

namespace Layerok\PosterPos\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddBannersTable extends Migration
{
    public string $tableName = 'layerok_posterpos_banners';
    ///
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->bigInteger('product_id')
                ->unsigned()
                ->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}


