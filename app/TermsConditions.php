<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TermsConditions extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Returns list of invoice schemes in array format
     */
    public static function forDropdown($business_id)
    {
        $dropdown = TermsConditions::where('business_id', $business_id)
            ->pluck('name', 'id');

        return $dropdown;
    }

    /**
     * Retrieves the default invoice scheme
     */
    public static function getDefault($business_id)
    {
        $default = TermsConditions::where('business_id', $business_id)
            ->where('is_default', 1)
            ->first();
        return $default;
    }

    public static function saveTermsConditions($business_id, $today, $user_id, $ip, $clieintIP, $otp)
    {
        $data = array(
            array('business_id' => $business_id, 'today' => $today, 'user_id' => $user_id, 'ip' => $ip, 'otp' => $otp, 'clieintIP' => $clieintIP),
        );
        $res = TermsConditions::insert($data);
        return res;
    }
}
