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
        Schema::create('deliverect_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 255);
            $table->string('pos_location', 255);
            $table->string('account_id', 255);
            $table->string('location_id', 255)->unique();
            $table->string('external_Location_id', 255);
            $table->string('location_name');
            $table->timestamps();

            $table->unique(['business_id', 'pos_location']);
        });
    }
};
