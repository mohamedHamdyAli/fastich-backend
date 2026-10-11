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
        Schema::table('business', function (Blueprint $table) {
            $table->string('woocommerce_wh_oc_secret')->after('woocommerce_skipped_orders')->nullable();
            $table->string('woocommerce_wh_ou_secret')->after('woocommerce_skipped_orders')->nullable();
            $table->string('woocommerce_wh_od_secret')->after('woocommerce_skipped_orders')->nullable();
            $table->string('woocommerce_wh_or_secret')->after('woocommerce_skipped_orders')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('woocommerce_wh_oc_secret');
            $table->dropColumn('woocommerce_wh_ou_secret');
            $table->dropColumn('woocommerce_wh_od_secret');
            $table->dropColumn('woocommerce_wh_or_secret');

        });
    }
};
