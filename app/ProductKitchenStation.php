<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductKitchenStation extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
      'product_id',  
      'kitchen_station_id', 
    ];
}
