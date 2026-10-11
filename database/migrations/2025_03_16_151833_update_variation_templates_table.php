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
        Schema::table('variation_templates', function (Blueprint $table) {
            $table->integer('woocommerce_attr_id')->nullable()->after('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variation_templates', function (Blueprint $table) {
            $table->dropColumn('woocommerce_attr_id');
        });
    }
};
