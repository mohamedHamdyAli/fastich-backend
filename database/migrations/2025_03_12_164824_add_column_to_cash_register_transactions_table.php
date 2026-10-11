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
        Schema::table('cash_register_transactions', function (Blueprint $table) {
            $table->integer('client_app')->default(0)->after('pay_method');
            $table->decimal('discount_amount', 22, 4)->default(0)->after('pay_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_register_transactions', function (Blueprint $table) {
            $table->dropColumn('client_app');
            $table->dropColumn('discount_amount');
        });
    }
};
