<?php

namespace Layerok\Restapi\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class WidenOnlineOrdersCommentAndAddRealSpotId extends Migration
{
    public function up()
    {
        Schema::table('online_orders', function (Blueprint $table) {
            // The composed comment (client text, payment, cutlery, delivery time and
            // the bonus marker) already runs past 200 characters.
            $table->text('comment')->nullable()->change();

            // spot_id holds the Poster tablet id; this is our Spot, which the unpaid
            // order sweep needs to find WayForPay credentials and stock.
            $table->integer('real_spot_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('comment')->nullable()->change();
            $table->dropColumn('real_spot_id');
        });
    }
}
