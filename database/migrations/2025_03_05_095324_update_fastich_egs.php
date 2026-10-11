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
            $table->string('registered_business_name_ar')->after('registered_business_name')->nullable();
            $table->string('common_name')->after('registered_business_name_ar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fastich_egs', function (Blueprint $table) {
            $table->dropColumn('registered_business_name_ar');
            $table->dropColumn('common_name');
        });
    }
};
