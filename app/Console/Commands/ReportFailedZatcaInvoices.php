<?php

namespace App\Console\Commands;

use App\Models\ZatcaInvoiceHistory;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use App\Transaction;
use App\Utils\ZatcaUtil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportFailedZatcaInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:report_failed_zatca_invoices';

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
        // DB::beginTransaction();
        // Fetch invoices from the zatca_invoice_history table which are generate 24 hours ago and not reported
        $invoices = ZatcaInvoiceHistory::with(['business'])->where('reporting_status', 'NOT_REPORTED')
            ->where('created_at', '>=', now()->subHours(24))
            ->where('validation_status', '=', 'ERROR')
            ->orderBy('id', 'desc')
            ->get();
        foreach ($invoices as $invoice) {

            $response  = $zatca->generateZatcaInvoiceWithQrCode($invoice->transaction_id, 1);
            // log the response
            $business = $invoice->business;
            $certData = $zatca->getCertificateData($business->slug);
            try {
                $validation = $zatca->zatcaClient->submitReportingInvoice(
                    $certData['certificate'],
                    $certData['secret'],
                    $response['invoice'],
                    $response['invoiceHash'],
                    $response['uuid']
                );
                Log::info('ZATCA Submission Success On Retry: ' . $validation);
            } catch (ZatcaApiException $e) {
                // Log the exception
                Log::error('ZATCA Submission Error On Retry: ' . $e->getMessage(), $e->getContext()['response']);
                continue;
            } catch (\Exception $e) {
                // Log the exception
                Log::error('ZATCA Submission Error On Retry: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
                continue;
            }
            ZatcaInvoiceHistory::where('transaction_id', $invoice->transaction_id)->update(
                [
                    'reporting_status' => 'REPORTED',
                    'validation_status' => 'PASS',
                ]
            );
            Transaction::where('id', $invoice->transaction_id)->update(['generatedBase64String' => $response['qrCode']]);
        }
        // Commit the transaction
        // DB::commit();
    }
}
