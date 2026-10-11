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
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('total_expense', 22, 4)->default(0)->after('closing_amount');
            $table->decimal('remainder_cash_in_hand', 22, 4)->default(0)->after('closing_amount');
            $table->integer('input_cash_in_hand')->nullable()->after('closing_amount');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn('total_expense');
            $table->dropColumn('remainder_cash_in_hand');
            $table->dropColumn('input_cash_in_hand');
        });
    }
};
