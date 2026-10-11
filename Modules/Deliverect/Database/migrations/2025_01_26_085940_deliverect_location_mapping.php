<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deliverect_location_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 255);
            $table->string('pos_location', 255);
            $table->string('deliverect_loc_id');
            $table->timestamps();

            $table->unique(['business_id', 'pos_location']);
            $table->unique('deliverect_loc_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deliverect_location_mappings');
    }
};
