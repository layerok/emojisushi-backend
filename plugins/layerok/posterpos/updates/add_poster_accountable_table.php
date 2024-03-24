<?php

namespace Layerok\PosterPos\Updates;

use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use Schema;
use October\Rain\Database\Updates\Migration;
use October\Rain\Database\Schema\Blueprint;

use \Layerok\PosterPos\Models\PosterAccount;

use \OFFLINE\Mall\Models\Product;
use \OFFLINE\Mall\Models\Category;
use \OFFLINE\Mall\Models\Variant;

class AddPosterAccountableTable extends Migration
{
    public string $tableName = 'layerok_posterpos_poster_accountable';

    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->bigInteger('poster_account_id')->unsigned();
            $table->bigInteger('poster_id')->unsigned();
            $table->morphs('poster_accountable', 'poster_accountable');
        });


        $kador = PosterAccount::where('account_name', 'emojisushikador')->first();
        $bazarna = PosterAccount::where('account_name', 'emoji-bar2')->first();


        // migrate properties
        $records = collect([
            Property::all(),
            PropertyGroup::all(),
            Variant::all(),
            Category::all(),
            Product::all()
        ])->flatten(2);

        $records->each(function($record) use($bazarna, $kador) {
            $relations = [];

            if(!empty($record->poster_id) && !empty($bazarna)) {
                $relations[$bazarna->id] = [
                    'poster_id' => $record->poster_id,
                ];
            }

            if(!empty($record->poster_id2) && !empty($kador)) {
                $relations[$kador->id] = [
                    'poster_id' => $record->poster_id2,
                ];
            }

            $record->poster_accounts()->sync($relations);
        });


        Schema::dropColumns('offline_mall_products', ['poster_id', 'poster_id2']);
        Schema::dropColumns('offline_mall_product_variants', ['poster_id']);
        Schema::dropColumns('offline_mall_categories', ['poster_id']);
        Schema::dropColumns('offline_mall_property_groups', ['poster_id']);
        Schema::dropColumns('offline_mall_properties', ['poster_id']);
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);

        Schema::table('offline_mall_products', function(Blueprint $table) {
            $table->addColumn('integer', 'poster_id', ['nullable' => true]);
            $table->addColumn('integer', 'poster_id2', ['nullable' => true]);
        });
        Schema::table('offline_mall_categories', function(Blueprint $table) {
            $table->addColumn('integer', 'poster_id', ['nullable' => true]);
        });
        Schema::table('offline_mall_product_variants', function(Blueprint $table) {
            $table->addColumn('integer', 'poster_id', ['nullable' => true]);
        });
        Schema::table('offline_mall_property_groups', function(Blueprint $table) {
            $table->addColumn('integer', 'poster_id', ['nullable' => true]);
        });
        Schema::table('offline_mall_properties', function(Blueprint $table) {
            $table->addColumn('integer', 'poster_id', ['nullable' => true]);
        });
    }
}


