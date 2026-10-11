<?php

namespace Modules\Deliverect\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Deliverect\Database\factories\DeliverectRegistrationFactory;

class DeliverectRegistration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'business_id',
        'pos_location',
        'account_id',
        'location_id',
        'external_Location_id',
        'location_name'
    ];
}
