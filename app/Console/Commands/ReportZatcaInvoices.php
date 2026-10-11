<?php

namespace App\Console\Commands;

use App\Models\ZatcaInvoiceHistory;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use App\Utils\ZatcaUtil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportZatcaInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:report_zatca_invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reports ZATCA invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zatca = new ZatcaUtil();
        // Db transaction
        DB::beginTransaction();
        // Fetch invoices from the zatca_invoice_history table which are generate 24 hours ago and not reported
        $invoices = ZatcaInvoiceHistory::with(['business'])->where('reporting_status', 'NOT_REPORTED')
            ->where('created_at', '>=', now()->subHours(24))
            ->where('validation_status', '!=', 'ERROR')
            ->get();
        foreach ($invoices as $invoice) {
            // Report the invoice
            $business = $invoice->business;
            $certData = $zatca->getCertificateData($business->slug);
            $name = $invoice->invoice_type . '_' . $invoice->invoice_number . '_' . $invoice->business_id . '_' .$invoice->location_id . "_signed.xml";
            //$name = $invoice->invoice_type . "_" . $invoice->invoice_number . "_signed.xml";
            $signedInvoice = $zatca->getSignedXmlInvoice($business->slug, $name);
            // $validatedResult = $this->validateInvoiceXml($businessData, $data['utf8Invoice'], $data['invoiceHash'], $invoiceData['uuid']);
            try {
                $validation = $zatca->zatcaClient->submitReportingInvoice(
                    $certData['certificate'],
                    $certData['secret'],
                    base64_encode($signedInvoice),
                    $invoice->ih,
                    $invoice->uuid
                );
            } catch (ZatcaApiException $e) {
                // Log the exception
                Log::error('ZATCA Submission Error: INV-ID:' . $invoice->id . ', message: ' . $e->getMessage(), $e->getContext()['response']);
                ZatcaInvoiceHistory::where('id', $invoice->id)->update(['validation_status' => 'ERROR']);
                continue;
            } catch (\Exception $e) {
                // Log the exception
                Log::error('ZATCA Submission Exception: INV-ID:' . $invoice->id . ', message: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
                continue;
            }
            // Log the validation response in file
            // Log::info('ZATCA Submission Response: ' . json_encode($validation));
            // Update the reporting status
            ZatcaInvoiceHistory::where('id', $invoice->id)->update(['reporting_status' => 'REPORTED', 'validation_status' => 'PASS']);
        }
        // Commit the transaction
        DB::commit();
    }
}
