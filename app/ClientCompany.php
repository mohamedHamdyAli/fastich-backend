<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientCompany extends Model
{

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $guarded = ['id'];

    public function clientcompanies()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Client App Dropdown
     *
     * @param int $business_id
     * @return array
     */
    public static function forDropdown($business_id)
    {
        $clientApp = ClientCompany::where('business_id', $business_id)
            ->orderBy('id', 'asc')
            ->get();
        $dropdown = $clientApp->pluck('app_name', 'id');
        return $dropdown;
    }
}
