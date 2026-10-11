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
        Schema::create('call_number_schemes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index('business_id');
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('business_locations');
            $table->index('location_id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id');
            $table->integer('start_number')->default(1);
            $table->integer('call_number_count')->default(0);
            $table->integer('last_call_number_end')->default(999);
            $table->integer('total_digits')->default(3);
            $table->timestamps();
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_number_schemes');
    }
};
