<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryLocation extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $guarded = ['catlocID'];

    public function categories()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
