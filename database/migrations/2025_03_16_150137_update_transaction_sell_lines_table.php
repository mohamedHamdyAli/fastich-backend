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
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->boolean('sell_line_order_opnened')->default(0)->after('product_id');
            $table->decimal('line_discount', 22, 4)->default(0)->after('line_discount_type');
            $table->integer('woocommerce_line_items_id')->nullabale()->after('sell_line_note');
            $table->decimal('sell_price_inc_tax',22, 4)->default(0)->after('unit_price_inc_tax');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn('sell_line_order_opnened');
            $table->dropColumn('line_discount');
            $table->dropColumn('woocommerce_line_items_id');
            $table->dropColumn('sell_price_inc_tax');
        });
    }
};
