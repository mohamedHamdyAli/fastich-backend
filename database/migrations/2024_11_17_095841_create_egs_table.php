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
        Schema::create('fastich_egs', function (Blueprint $table) {
            $table->id();
            // Unique identifiers
            $table->string('business_id', 50)->unique()->index();
            $table->string('egs_id', 50)->unique()->index();
            $table->string('crn_number', 50)->unique()->index();
            $table->string('vat_number', 50)->unique()->index();
            // Business details
            $table->string('registered_business_name', 255);
            $table->string('registered_address', 255);
            $table->string('business_category', 100);
            // Location details
            $table->string('city_name', 100);
            $table->string('city_subdivision_name', 100);
            $table->string('street_name', 255);
            $table->string('postal_zone', 20);
            $table->string('building_number', 50);
            // EGS Details
            $table->uuid('uuid');
            // Soft delete
            $table->softDeletes();
            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fastich_egs');
    }
};
