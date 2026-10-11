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
            $table->string('return_reason', 255)->after('staff_note');
            $table->string('return_payment_method',255)->after('staff_note');
            $table->string('card_transaction_id', 255)->after('staff_note');
            $table->integer('woocommerce_order_id')->nullable()->after('mfg_is_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('return_reason');
            $table->dropColumn('return_payment_method');
            $table->dropColumn('card_transaction_id');
            $table->dropColumn('woocommerce_order_id');
        });
    }
};
