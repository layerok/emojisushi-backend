<?php

namespace OFFLINE\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class UpdateDescriptionShortColumnOfProductVariantsTable extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_product_variants', function ($table) {
            $table->text('description_short')->change();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_product_variants', function ($table) {
            $table->string('description_short', 255)->change();
        });
    }
}
