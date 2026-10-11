<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaxAndTaxTypeFieldInVariationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variations', function (Blueprint $table) {
            $table->integer('tax_id')->unsigned()->after('sell_price_inc_tax')->nullable();
            $table->string('tax_type')->after('tax_id')->nullable();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onUpdate('cascade')->onDelete('set null');
        });
        // Schema::table('variations', function (Blueprint $table) {
        //      $table->dropColumn('tax_id');
        //      $table->dropColumn('tax_type');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variations');

    }
}
