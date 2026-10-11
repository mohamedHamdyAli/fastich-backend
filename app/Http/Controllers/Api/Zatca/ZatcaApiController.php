<?php

namespace App\Http\Controllers\Api\Zatca;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FastichEgs;
use App\Models\ZatcaInvoiceHistory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ReportingInvoiceRequest;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use App\Services\Zatca\InvoiceCreator\ZatcaAPI;
use App\Transaction;
use App\Utils\ZatcaUtil;
use Exception;

class ZatcaApiController extends Controller
{
    const onLive = false;

    private $zatcaClient;

    public function __construct()
    {
        $this->zatcaClient = new ZatcaUtil();
    }

    /**
     * Validate and OnBoard the EGS for Zatca API endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onBoardEgs(Request $request)
    {
        // Validate the incoming request data
        $validator = $this->validateBusinessRegistrationData($request->all());

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $validatedData = $validator->validated();
            $businessRecord = $this->processBusinessRegistration($validatedData);
            return response()->json($businessRecord);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to register business',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate business registration data
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateBusinessRegistrationData(array $data)
    {
        return Validator::make($data, [
            'business_id' => 'required|string|max:50|unique:fastich_egs,business_id',
            'egsId' => 'required|string|max:50|unique:fastich_egs,egs_id',
            'crnNumber' => 'required|string|max:50|unique:fastich_egs,crn_number',
            'vatNumber' => 'required|string|max:50|unique:fastich_egs,vat_number',
            'registeredBussinessName' => 'required|string|max:64',
            'registeredBussinessNameAr' => 'required|string|max:64',
            'commonName' => 'required|string|max:64',
            'registeredAddress' => 'required|string|max:255',
            'bussinessCategory' => 'required|string|max:100',
            'cityName' => 'required|string|max:100',
            'citySubdivisionName' => 'required|string|max:100',
            'streetName' => 'required|string|max:255',
            'postalZone' => 'required|string|max:20',
            'buildingNumber' => 'required|string|max:50',
            'otp' => 'required|string|size:6'
        ]);
    }

    /**
     * Process business registration after validation
     *
     * @param array $validatedData
     * @return \App\Models\Business
     */
    private function processBusinessRegistration(array $validatedData)
    {
        try {
            $uuid = Str::uuid();
            $sanitizedName = Str::slug($validatedData['registeredBussinessName'], '_');
            $businessData = [
                'business_id' => $validatedData['business_id'],
                'egs_id' => $validatedData['egsId'],
                'crn_number' => $validatedData['crnNumber'],
                'vat_number' => $validatedData['vatNumber'],
                'registered_business_name' => $validatedData['registeredBussinessName'],
                'common_name' => $validatedData['commonName'],
                'registered_business_name_ar' => $validatedData['registeredBussinessNameAr'],
                'registered_address' => $validatedData['registeredAddress'],
                'business_category' => $validatedData['bussinessCategory'],
                'city_name' => $validatedData['cityName'],
                'city_subdivision_name' => $validatedData['citySubdivisionName'],
                'street_name' => $validatedData['streetName'],
                'postal_zone' => $validatedData['postalZone'],
                'building_number' => $validatedData['buildingNumber'],
                'uuid' => $uuid,
                'slug' => $validatedData['business_id'] . '_' . $sanitizedName
            ];
            // Create business record

            $complianceCertificate = $this->zatcaClient->generateCertificate($businessData,  $validatedData['otp']);

            // Generate 3 invoices i.e. 
            // 1: Simplified Invoice
            // 2: Simplified Credit Note
            // 3: Simplified Debit Note
            // and validate them
            $uuid = Str::uuid();

            $sampleInvoiceData = [
                'uuid' => $uuid,
                'icv' => 23,
                'invoice_no' => $uuid,
                'pih' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                'tax_rate' => 5,
                'business_id' => $businessData['business_id'],
                'location_id' => 0,
                'issue_datetime' => new \DateTime(),
                'items' => [
                    [
                        'product_id' => '123',
                        'name' => 'Pizza',
                        'quantity' => 2,
                        'unit_price' => 10
                    ],
                    [
                        'product_id' => '15232',
                        'name' => 'Burger',
                        'quantity' => 3,
                        'unit_price' => 3
                    ],
                ],
            ];
            
            $reportingInvoice = $this->zatcaClient->generateInvoiceXML($sampleInvoiceData, $businessData, 'invoice', true);
            $validatedInvoice = $this->zatcaClient->validateInvoiceXml($businessData, $reportingInvoice['utf8Invoice'], $reportingInvoice['invoiceHash'], $sampleInvoiceData['uuid']);
            $reportingInvoice = $this->zatcaClient->generateInvoiceXML($sampleInvoiceData, $businessData,  'credit', true);
            $validatedCredit = $this->zatcaClient->validateInvoiceXml($businessData, $reportingInvoice['utf8Invoice'], $reportingInvoice['invoiceHash'], $sampleInvoiceData['uuid']);
            $reportingInvoice = $this->zatcaClient->generateInvoiceXML($sampleInvoiceData, $businessData,  'debit', true);
            $validatedDebit = $this->zatcaClient->validateInvoiceXml($businessData, $reportingInvoice['utf8Invoice'], $reportingInvoice['invoiceHash'], $sampleInvoiceData['uuid']);

            $validations = [
                'invoice' => $validatedInvoice,
                'credit' => $validatedCredit,
                'debit' => $validatedDebit
            ];

            $productionCertificate = $this->zatcaClient->getProductionCertificate($complianceCertificate->getCertificate(), $complianceCertificate->getSecret(), $complianceCertificate->getRequestId());
            if ($productionCertificate) {
                $data['response']['bussiness'] = FastichEgs::create($businessData);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Business registered successfully',
                    'response' => $businessData,
                    'validations' => $validations,
                    'statusCode' => 200
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate invoices',
                'response' => $businessData,
                'validations' => $validations,
                'statusCode' => 500
            ]);
        } catch (\Exception $e) {
            // If an error occurs, delete the created business record
            if (isset($business)) {
                $business->delete();
            }
            Log::error($e->getMessage());
            // Rethrow the exception to be handled by the caller
            throw $e;
        }
    }


    public function renewProductionCertificate(Request $request)
    {
        try {
            $request->validate([
                'business_id' => 'required|string',
                'otp' => 'required|string|size:6'
            ]);
            $bussinessData = FastichEgs::where('business_id', $request->business_id)->first();
            if ($bussinessData == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business not yet registered',
                    'statusCode' => 404
                ]);
            }
            $certData = $this->zatcaClient->getCertificateData($bussinessData->slug);
            $csr = $this->zatcaClient->getCsrFromFile();
            $cleanCsr = trim(str_replace(["-----BEGIN CERTIFICATE REQUEST-----", "-----END CERTIFICATE REQUEST-----"], "", $csr));
            $check = $this->zatcaClient->renewProductionCertificate($certData['certificate'], $certData['secret'], $cleanCsr, $request->otp);
            if (!$check || !$check['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate certificate',
                    'response' => $check,
                    'statusCode' => 500
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Certificate renewed successfully',
                'response' => $check,
                'statusCode' => 200
            ]);
        } catch (ZatcaApiException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate certificate',
                'response' => $e->getContext()['response'],
                'statusCode' => $e->getCode()
            ], $e->getCode());
        } catch (Exception $e) {
            throw $e;
        }
    }



    public function reportInvoice(Request $request)
    {
        try {
            //validate the request
            $request->validate([
                'transaction_no' => 'required|string',
            ]);
            $transaction = Transaction::with(['business', 'location', 'zatca_invoice_history'])->where("is_suspend", "=", 0)->find($request->transaction_no);
            if ($transaction == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found',
                    'statusCode' => 404
                ], 404);
            }
            if (isset($transaction->zatca_invoice_history) && $transaction->zatca_invoice_history->reporting_status == 'REPORTED') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice already reported',
                    'statusCode' => 400
                ], 400);
            }

            if ($transaction->is_suspend == 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Suspended transaction cannot be reported',
                    'statusCode' => 400
                ], 400);
            }
            if ($transaction->type == 'sell' && $transaction->status == 'final' && in_array($transaction->payment_status, ['paid', 'partial'])) {
                $type = 'invoice';
            } elseif ($transaction->type == 'sell' && $transaction->status == 'final' && $transaction->payment_status == 'returned') {
                $type = 'credit';
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid transaction type',
                    'statusCode' => 400
                ], 400);
            }
            $response  = $this->zatcaClient->generateZatcaInvoiceWithQrCode($request->transaction_no);
            // log the response
            Log::info('ZATCA Invoice Generation Response: ' . json_encode($response));
            $response = $this->reportInvoiceToZatca($request->transaction_no);

            // Update the reporting status            
            ZatcaInvoiceHistory::where('transaction_id', $request->transaction_no)->update(['reporting_status' => 'REPORTED']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice reported successfully',
                'response' => $response,
                'statusCode' => 200
            ]);
        } catch (ZatcaApiException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to report invoice',
                'response' => $e->getContext()['response'],
                'statusCode' => $e->getCode()
            ], $e->getCode());
        } catch (Exception $e) {
            // log the exception
            Log::error($e);
            return response()->json([
                'status' => 'exception',
                'message' => 'Failed to report invoice',
                'response' => $e->getMessage(),
                'statusCode' => 500
            ], 500);
        }
    }

    // remove all the folder and delete all the records from the database
    public function deleteCredentials(Request $request)
    {
        $egsRecords = FastichEgs::all();
        foreach ($egsRecords as $egsRecord) {
            // $folder_path = "zatca_credentials/{$egsRecord->}_{$egsRecord->business_id}";
            // Storage::deleteDirectory($folder_path);
            $egsRecord->delete();
        }
        return response()->json(['message' => 'All credentials deleted successfully']);
    }

    private function reportInvoiceToZatca(string $trasnactionId)
    {
        $zatca = new ZatcaUtil();
        $invoice = ZatcaInvoiceHistory::where('transaction_id', $trasnactionId)->first();
        $business = $invoice->business;
        $certData = $zatca->getCertificateData($business->slug);
        $name = $invoice->invoice_type . "_" . $invoice->invoice_number . "_" . $invoice->business_id . "_" . $invoice->location_id . "_signed.xml";
        $signedInvoice = $zatca->getSignedXmlInvoice($business->slug, $name);
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
            Log::error('ZATCA Submission Error: ' . $e->getMessage(), $e->getContext()['response']);
            throw $e;
        } catch (\Exception $e) {
            // Log the exception
            Log::error('ZATCA Submission Error: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
            throw $e;
        }
        return $validation;
    }
}
