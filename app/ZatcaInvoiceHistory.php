<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZatcaInvoiceHistory extends Model
{
    use HasFactory;

    protected $table = 'zatca_invoice_history';

    protected $fillable = [
        'business_id',
        'icv',
        'ih',
        'pih',
        'validation_status',
        'reporting_status',
    ];
}
