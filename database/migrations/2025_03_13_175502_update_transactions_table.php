<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('call_no')->nullable()->after('invoice_no');
            $table->text('deliverect_order_id')->nullable()->after('invoice_no');
            $table->text('generatedBase64String')->nullable()->after('invoice_no'); 
            $table->boolean('order_opnened')->default(0)->after('invoice_no');
            $table->integer('order_type_Id')->unsigned()->after('invoice_no');
            $table->foreign('order_type_Id')->references('id')->on('order_types')->onDelete('cascade');
            $table->float('tip', 8, 2)->after('pay_term_type');
            $table->float('delivery_cost', 8, 2)->after('pay_term_type');
            $table->float('service_charge', 8, 2)->after('pay_term_type');
            $table->float('bag_fee', 8, 2)->after('pay_term_type');
            $table->string('clientCompany')->nullable()->after('pay_term_type');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
          $table->dropcolumn('call_no');
          $table->dropcolumn('deliverect_order_id');
          $table->dropcolumn('delivery_cost');
          $table->dropcolumn('service_charge');
          $table->dropcolumn('bag_fee');
          $table->dropcolumn('clientCompany');
        });
    }
};
