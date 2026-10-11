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
        Schema::table('zatca_invoice_history', function (Blueprint $table) {
            // add enum type column with values invoice, credit , debit default to null
            $table->enum('invoice_type', ['invoice', 'credit', 'debit'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zatca_invoice_history', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
};
