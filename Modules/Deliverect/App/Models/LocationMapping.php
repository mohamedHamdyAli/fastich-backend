<?php

namespace Modules\Deliverect\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationMapping extends Model
{
    use HasFactory;

    protected $table = 'deliverect_location_mappings';

    protected $fillable = [
        'business_id',
        'pos_location',
        'deliverect_loc_id'
    ];
}