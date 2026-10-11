<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class FastichEgs extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fastich_egs';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'egs_id',
        'crn_number',
        'vat_number',
        'registered_business_name',
        'registered_business_name_ar',
        'common_name',
        'registered_address',
        'business_category',
        'city_name',
        'city_subdivision_name',
        'street_name',
        'postal_zone',
        'building_number',
        'is_active',
        'uuid',
        'slug',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope a query to find a business by its unique identifiers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $identifier
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindByIdentifier($query, $identifier)
    {
        return $query->where(function ($q) use ($identifier) {
            $q->where('business_id', $identifier)
                ->orWhere('egs_id', $identifier)
                ->orWhere('crn_number', $identifier)
                ->orWhere('vat_number', $identifier);
        });
    }

    /**
     * Generate a unique business identifier.
     *
     * @return string
     */
    public static function generateUniqueBusinessId()
    {
        do {
            $businessId = 'BUS' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('business_id', $businessId)->exists());

        return $businessId;
    }
}
