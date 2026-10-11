<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FastichEgs extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'business_name',
        'uuid',
        'custom_id',
        'model',
        'vat_name',
        'vat_number',
        'branch_name',
        // location
        'city',
        'city_subdivision',
        'street',
        'plot_identification',
        'building',
        'postal_zone',
        'branch_industry',
        'crn_number',
    ];
}
