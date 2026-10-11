<?php

namespace Modules\Deliverect\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverectChannel extends Model
{
    protected $table = 'deliverect_channels';

    protected $fillable = [
        'business_id',
        'channel_id',
        'channel_name'
    ];
}
