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
        Schema::table('fastich_egs', function (Blueprint $table) {
            //add slug column
            $table->string('slug')->after('egs_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fastich_egs', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
