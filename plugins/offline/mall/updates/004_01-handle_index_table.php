<?php

namespace OFFLINE\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class HandleIndexTable extends Migration
{
    public function up()
    {
    }

    public function down()
    {
        Schema::dropIfExists('offline_mall_index');
    }
}
