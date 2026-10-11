<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWeightAndProductDescFieldInBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->integer('enable_weight')->default(false)->after('enable_editing_product_from_purchase');
            $table->integer('enable_product_description_imei_or_serial_number')->default(false)->after('enable_weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('enable_weight');
            $table->dropColumn('enable_product_description_imei_or_serial_number');
        });
    }
}
