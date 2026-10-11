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
        // Add location to zatca_invoice_history table
        // drop existing unique constraint business 
        Schema::table('zatca_invoice_history', function (Blueprint $table) {
            $table->string('location_id')->nullable()->after('business_id');
            $table->dropUnique('unique_business_invoice_icv');
            $table->unique(['business_id', 'icv', 'location_id'], 'unique_business_invoice_icv');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zatca_invoice_history', function (Blueprint $table) {
            $table->dropColumn('location_id');
            $table->dropUnique('unique_business_invoice_icv');
            $table->unique(['business_id', 'icv'], 'unique_business_invoice_icv');
        });
    }
};
