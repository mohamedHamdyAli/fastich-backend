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
        Schema::create('deliverect_orders', function (Blueprint $table) {
            $table->string('id');
            $table->string('business_id', 255);
            $table->string('pos_location', 255); 
            $table->string('account');
            $table->string('channelOrderId');
            $table->string('channelOrderDisplayId');
            $table->string('posId')->nullable();
            $table->string('posReceiptId')->nullable();
            $table->string('posLocationId')->nullable();
            $table->string('location');
            $table->string('channelLink');
            $table->string('status');
            $table->integer('preparationTime')->default(15);
            $table->integer('reason')->nullable();
            $table->json('statusHistory'); // JSON field for status history
            $table->json('packaging')->nullable();
            $table->string('by');
            $table->string('orderType');
            $table->integer('channel');
            $table->integer('pos');
            $table->json('rating')->nullable();
            $table->timestamp('pickupTime')->nullable();
            $table->timestamp('deliveryTime')->nullable();
            $table->boolean('deliveryIsAsap');
            $table->json('courier')->nullable();
            $table->json('courierUpdateHistory')->nullable();
            $table->json('customer'); // Customer Details
            $table->json('deliveryAddress'); // Delivery Address
            $table->boolean('orderIsAlreadyPaid');
            $table->json('taxes')->nullable();
            $table->json('payment'); // Payment Details
            $table->text('note')->nullable();
            $table->json('items'); // Ordered Items
            $table->integer('decimalDigits');
            $table->integer('numberOfCustomers');
            $table->string('channelOrderRawId');
            $table->json('channelOrderHistoryRawIds')->nullable();
            $table->float('serviceCharge', 8, 2);
            $table->float('deliveryCost', 8, 2);
            $table->float('bagFee', 8, 2);
            $table->float('tip', 8, 2);
            $table->float('driverTip', 8, 2);
            $table->float('discountTotal', 8, 2);
            $table->json('discounts')->nullable();
            $table->json('capacityUsages')->nullable();
            $table->boolean('recent');
            $table->string('resolvedBy')->nullable();
            $table->string('brandId');
            $table->boolean('testOrder');
            $table->string('timezone');
            $table->date('date');
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliverect_orders');
    }
};
