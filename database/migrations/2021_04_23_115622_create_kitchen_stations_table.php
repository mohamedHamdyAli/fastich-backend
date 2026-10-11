<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKitchenStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kitchen_stations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->unsigned()->nullable();
            $table->integer('business_location_id')->unsigned()->nullable();
            $table->string('station_name')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('cascade');
        });
        
        Schema::table('business_locations', function (Blueprint $table){
          $table->boolean('is_active_kitchen_station')->default(1)->after('is_active'); 
        });
        
        Schema::table('users',function (Blueprint $table){
            $table->unsignedBigInteger('kitchen_station_id')->after('business_id')->nullable();
            $table->foreign('kitchen_station_id')->references('id')
            ->on('kitchen_stations')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kitchen_stations');
    }
}
