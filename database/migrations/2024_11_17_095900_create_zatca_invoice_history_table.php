<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('zatca_invoice_history', function (Blueprint $table) {

            $table->id();

            // Business Identification
            $table->string('business_id')->index();

            // Zatca Specific Fields
            $table->unsignedInteger('icv')->comment('Increment Count Value');
            $table->string('ih')->comment('Invoice Hash');
            $table->string('pih')->comment('Previous Invoice Hash');

            // Invoice Details
            $table->string('invoice_number')->nullable()->comment('Unique invoice number');
            $table->date('invoice_date')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable()->comment('Total invoice amount');

            // Validation and Reporting Status
            $table->enum('validation_status', ['ERROR', 'WARNING', 'PASS'])
                ->default('PASS')
                ->index();

            $table->enum('reporting_status', ['NOT_REPORTED', 'PASS', 'REPORTED'])
                ->default('NOT_REPORTED')
                ->index();

            // Timestamps
            $table->timestamps();

            // Unique constraint for preventing duplicate entries
            $table->unique(['business_id', 'icv'], 'unique_business_invoice_icv');
            $table->unique(['business_id', 'ih'], 'unique_business_invoice_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_invoice_history');
    }
};
