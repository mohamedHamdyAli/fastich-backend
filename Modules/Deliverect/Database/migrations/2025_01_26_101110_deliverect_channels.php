<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deliverect_channels', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 255);
            $table->string('channel_id');
            $table->string('channel_name');
            $table->timestamps();
            $table->unique(['business_id', 'channel_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('deliverect_channels');
    }
};
