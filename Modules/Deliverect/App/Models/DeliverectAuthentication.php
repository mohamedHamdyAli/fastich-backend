<?php

namespace Modules\Deliverect\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliverectAuthentication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'business_id',
        'client_id',
        'client_secret',
        'token_type',
        'access_token',
        'expires_at'
    ];


}
