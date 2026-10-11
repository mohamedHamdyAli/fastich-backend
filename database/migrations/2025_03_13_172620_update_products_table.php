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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('woocommerce_media_id')->nullable()->after('image');
            $table->integer('woocommerce_product_id')->nullable()->after('image');
            $table->boolean('woocommerce_disable_sync')->default(0)->after('image');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('woocommerce_media_id');
            $table->dropColumn('woocommerce_product_id');
            $table->dropColumn('woocommerce_disable_sync');
        });
    }
};
