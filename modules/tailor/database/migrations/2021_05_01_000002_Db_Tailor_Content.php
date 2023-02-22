<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tailor_content_schema', function (Blueprint $table) {
            $table->increments('id');
            $table->string('table_name')->nullable()->index();
            $table->mediumText('meta')->nullable();
            $table->mediumText('fields')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tailor_content_schema');
    }
};
