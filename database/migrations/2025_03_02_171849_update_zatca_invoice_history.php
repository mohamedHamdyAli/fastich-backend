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
            $table->string('uuid')->after('id')->nullable();
            $table->string('irn')->after('invoice_number')->nullable();
            $table->text('validation_response')->after('irn')->nullable();
            //foreign key to transactions table
            $table->unsignedInteger('transaction_id')->after('business_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zatca_invoice_history', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn('transaction_id');
            $table->dropColumn('irn');
            $table->dropColumn('uuid');
        });
    }
};
