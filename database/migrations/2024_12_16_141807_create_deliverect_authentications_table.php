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
        Schema::create('deliverect_authentications', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 255)->unique(); 
            $table->string('client_id', 255); 
            $table->string('client_secret', 255);
            $table->string('token_type');
            $table->text('access_token'); 
            $table->integer('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliverect_authentications');
    }
};
