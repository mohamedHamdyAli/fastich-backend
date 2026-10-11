<?php
namespace App\Http\Requests;

use App\Models\ZatcaInvoiceHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportingInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize()
    {
        // Add any authorization logic if needed
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules()
    {
        return [
            'transaction_no'     => [
                'required',
                'string',
                'max:50',
                // 'unique:invoices,transaction_no' // Assumes you have an invoices table
            ],
            'invoice_no'         => [
                'required',
                'integer',
                'min:1',
                // 'unique:invoices,invoice_no'
            ],
            'business_id'        => [
                'required',
                'string',
                'exists:fastich_egs,business_id', // Assumes you have a businesses table
            ],
            'tax_rate'           => [
                'required',
                'numeric',
                'min:0',
            ],
            'items'              => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.product_id' => [
                'required',
                'string',
                // 'exists:products,id' // Assumes you have a products table
            ],
            'items.*.name'       => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.quantity'   => [
                'required',
                'numeric',
                'min:1',
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors
     *
     * @return array
     */
    public function messages()
    {
        return [
            'transaction_no.unique'     => 'This transaction number has already been used.',
            'invoice_no.unique'         => 'This invoice number has already been used.',
            'items.min'                 => 'At least one item is required.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.quantity.min'      => 'Quantity must be at least 1.',
            'items.*.unit_price.min'    => 'Unit price cannot be negative.',
        ];
    }

    /**
     * Validate the request and handle the data
     *
     * @return array
     */
    public function processInvoice()
    {
        // Validate the request first
        $validatedData = $this->validated();

        $invoiceItems = $validatedData['items'];
        $taxRate      = $validatedData['tax_rate']; // 15% tax rate

        $processedItems      = [];
        $totalPriceBeforeTax = 0;
        $totalTaxAmount      = 0;
        $totalAmountWithTax  = 0;

        foreach ($invoiceItems as $item) {
            $unitPrice = $item['unit_price'];
            $quantity  = $item['quantity'];

            // Calculate item-level details
            $itemTotalPriceBeforeTax = $unitPrice * $quantity;
            $itemTaxAmount           = $itemTotalPriceBeforeTax * $taxRate;
            $itemTotalAmountWithTax  = $itemTotalPriceBeforeTax + $itemTaxAmount;

            // Prepare processed item details
            $processedItem = $item + [
                'total_price_before_tax' => round($itemTotalPriceBeforeTax, 2),
                'tax_amount'             => round($itemTaxAmount, 2),
                'total_amount_with_tax'  => round($itemTotalAmountWithTax, 2),
            ];

            $processedItems[] = $processedItem;

            // Aggregate totals
            $totalPriceBeforeTax += $itemTotalPriceBeforeTax;
            $totalTaxAmount += $itemTaxAmount;
            $totalAmountWithTax += $itemTotalAmountWithTax;
        }

        $egsRecord = ZatcaInvoiceHistory::getLastBusinessInvoice($validatedData['business_id']);
        $pih       = '';
        $icv       = '';
        if ($egsRecord) {
            $pih = $egsRecord->ih;
            $icv = (int) $egsRecord->icv + 1;
        } else {
            $pih = 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==';
            $icv = 1;
        }

        return [
            'transaction_no' => $validatedData['transaction_no'],
            'invoice_no'     => $validatedData['invoice_no'],
            'business_id'    => $validatedData['business_id'],
            'tax_rate'       => $taxRate,
            "pih"            => $pih,
            "icv"            => $icv,
            "uuid"           => Str::uuid(),
            "issue_datetime" => new \DateTime(),
            'items'          => $processedItems,
            'summary'        => [
                'total_price_before_tax' => round($totalPriceBeforeTax, 2),
                'total_tax_amount'       => round($totalTaxAmount, 2),
                'total_amount_with_tax'  => round($totalAmountWithTax, 2),
            ],
        ];
    }
}
