<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderTypes extends Model
{

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $guarded = ['id'];

    /**
     * Client App Dropdown
     *
     * @param int $business_id
     * @return array
     */
    public static function forDropdown($id)
    {
        $order_types = ClientCompany::where('id', $id)
            ->orderBy('id', 'asc')
            ->get();
        $dropdown = $order_types->pluck('order_name', 'id');
        return $dropdown;
    }
}
