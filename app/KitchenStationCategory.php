<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KitchenStationCategory extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['kID'];

    public function location()
    {
        return $this->belongsTo(Category::class, 'kitchecnCatID');
    }
}
