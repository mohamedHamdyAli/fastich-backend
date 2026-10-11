<?php

namespace Modules\Deliverect\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Deliverect\Database\factories\DeliverectOrderFactory;

class DeliverectOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'deliverect_order_id',
        'business_id',
        'pos_location',
        'account',
        'channelOrderId',
        'channelOrderDisplayId',
        'posId',
        'posReceiptId',
        'location',
        'channelLink',
        'status',
        'statusHistory',
        'packaging',
        'by',
        'preparationTime',
        'reason',
        'orderType',
        'channel',
        'pos',
        'rating',
        'pickupTime',
        'deliveryTime',
        'deliveryIsAsap',
        'courier',
        'courierUpdateHistory',
        'customer',
        'deliveryAddress',
        'orderIsAlreadyPaid',
        'taxes',
        'payment',
        'note',
        'items',
        'decimalDigits',
        'numberOfCustomers',
        'channelOrderRawId',
        'channelOrderHistoryRawIds',
        'serviceCharge',
        'deliveryCost',
        'bagFee',
        'tip',
        'driverTip',
        'discountTotal',
        'discounts',
        'capacityUsages',
        'recent',
        'resolvedBy',
        'brandId',
        'testOrder',
        'timezone',
        'date',
        'tags',
    ];

    protected $casts = [
        'statusHistory' => 'array',
        'packaging' => 'array',
        'rating' => 'array',
        'courier' => 'array',
        'courierUpdateHistory' => 'array',
        'customer' => 'array',
        'deliveryAddress' => 'array',
        'taxes' => 'array',
        'payment' => 'array',
        'items' => 'array',
        'discounts' => 'array',
        'capacityUsages' => 'array',
        'tags' => 'array',
    ];
    
    // protected static function newFactory(): DeliverectOrderFactory
    // {
    //     //return DeliverectOrderFactory::new();
    // }
}
