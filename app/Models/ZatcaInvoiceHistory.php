<?php

namespace App\Models;

use App\Business;
use App\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ZatcaInvoiceHistory extends Model
{
    use HasFactory;

    protected $table = 'zatca_invoice_history';

    protected $fillable = [
        'business_id',
        'location_id',
        'ih',
        'pih',
        'icv',
        'validation_status',
        'reporting_status',
        'invoice_number',
        'invoice_date',
        'total_amount',
        'transaction_id',
        'irn',
        'validation_response',
        'uuid',
        'invoice_type',
    ];

    // Ensure icv is cast to integer
    protected $casts = [
        'icv' => 'integer',
    ];

    /**
     * Create a new invoice history record
     * 
     * @param array $data Invoice history data
     * @return self
     */
    public static function createInvoiceHistory(array $data)
    {
        // Validate required fields
        $requiredFields = ['business_id', 'ih', 'validation_status', 'reporting_status'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: $field");
            }
        }

        // Find the last record for this business to get the PIH and generate ICV
        $lastRecord = self::where('business_id', $data['business_id'])
            ->orderBy('icv', 'desc')
            ->first();

        // Set PIH to the last record's IH, or a default value if no previous record
        $data['pih'] = $lastRecord ? $lastRecord->ih : 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==';

        // Automatically generate and increment ICV
        $data['icv'] = $lastRecord ? $lastRecord->icv + 1 : 1;

        // Default validation and reporting status if not provided
        $data['validation_status'] = $data['validation_status'] ?? 'PASS';
        $data['reporting_status'] = $data['reporting_status'] ?? 'NOT_REPORTED';
        // Create and save the record
        return self::create($data);
    }

    /**
     * Retrieve all invoices for a specific business
     * 
     * @param string $businessId Business identifier
     * @param int $limit Limit the number of records (optional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBusinessInvoices(string $businessId, int $limit = null)
    {
        $query = self::where('business_id', $businessId)
            ->orderBy('icv', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get the last inserted invoice for a specific business
     * 
     * @param string $businessId Business identifier
     * @return self|null
     */
    public static function getLastBusinessInvoice(string $businessId, $locationId)
    {
        return self::where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->orderBy('icv', 'desc')
            ->first();
    }

    /**
     * Scope to filter by validation status
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status Validation status to filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithValidationStatus($query, string $status)
    {
        return $query->where('validation_status', $status);
    }

    // make belongsTo relation with transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    // make belongsTo relation with business
    public function business()
    {
        return $this->belongsTo(FastichEgs::class, 'business_id', 'business_id');
    }
}
