<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToExampleTable extends Migration
{
    public function up()
    {
        Schema::table('example', function (Blueprint $table) {
            $table->string('new_column')->nullable(); // Adding a new column
        });
    }
    public function down()
    {
        Schema::table('example', function (Blueprint $table) {
            $table->dropColumn('new_column'); // Dropping the added column
        });
    }
}
