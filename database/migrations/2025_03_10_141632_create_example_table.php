<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExampleTable extends Migration
{
    /**
     * Run the migrations.
     */

    public function up()
    {
        Schema::create('example', function (Blueprint $table) {
            $table->id();                            // Auto-incremental primary key
            $table->string('name');                  // A string column
            $table->text('description')->nullable(); // A text column (nullable)
            $table->timestamps();                    // Created_at and updated_at timestamps
        });
    }
    /**
     * Reverse the migrations.
     */

    public function down()
    {
        Schema::dropIfExists('example');
    }
}
