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

        Schema::create('category_locations', function (Blueprint $table) {
            $table->increments('catlocID');
            $table->unsignedInteger('category_id')->index();
            $table->unsignedInteger('location_id')->index();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_locations');
    }
};
