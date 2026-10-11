<?php
namespace App\Http\Controllers;

use App\Business;
use App\Models\FastichEgs;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use App\Utils\ZatcaUtil;
use Illuminate\Http\Request;

class ZatcaController extends Controller
{
    private $zatcaClient;

    public function __construct()
    {
        $this->zatcaClient = new ZatcaUtil();
    }
    /**
     * Display the Zatca integration index view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $data        = Business::with(['zatcaRegistration'])->where("id", "=", $business_id)->first();
        $zatca       = $data->zatcaRegistration;
        $uuid        = $zatca != null ? $zatca->uuid : \Str::uuid();

        return view('zatca.index', [
            'business_id' => $business_id,
            'data'        => $data,
            'business'    => $zatca,
            'uuid'        => $uuid,
        ]);
    }

    public function saveBusinessDetails(Request $request)
    {
        $validated = $request->validate([
            'business_id'                 => 'required|unique:fastich_egs,business_id',
            'uuid'                        => 'required|string',
            'egs_id'                      => 'required|string|min:2|max:255|unique:fastich_egs,egs_id',
            'registered_business_name'    => 'required|string|min:5|max:64',
            'registered_business_name_ar' => 'required|string|min:5|max:64',
            'common_name'                 => 'required|string|min:5|max:255',
            'vat_number'                  => 'required|string|min:15|max:15|unique:fastich_egs,vat_number|regex:/^3\d*3$/',
            'crn_number'                  => 'required|string|min:4|max:20|unique:fastich_egs,crn_number',
            'business_category'           => 'required|string|min:3|max:255',
            'registered_address'          => 'required|string|min:3|max:255',
            'city_name'                   => 'required|string|min:3|max:255',
            'city_subdivision_name'       => 'required|string|min:3|max:255',
            'street_name'                 => 'required|string|min:3|max:255',
            'postal_zone'                 => 'required|string|min:3|max:10',
            'building_number'             => 'required|string|min:3|max:10',
            'otp'                         => 'required|string|min:6|max:6',
        ]);
        $sanitizedName = \Str::slug($request->registered_business_name, '_');
        $slug          = $request->business_id . '_' . $sanitizedName;

        $businessData         = $validated;
        $businessData['slug'] = $slug;

        $complianceCertificate = $this->zatcaClient->generateCertificate($businessData, $businessData['otp']);
        // Generate 3 invoices i.e.
        // 1: Simplified Invoice
        // 2: Simplified Credit Note
        // 3: Simplified Debit Note
        // and validate them
        $uuid = \Str::uuid();

        $sampleInvoiceData = [
            'uuid'           => $uuid,
            'icv'            => 23,
            'invoice_no'     => $businessData['uuid'],
            'pih'            => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
            'tax_rate'       => 5,
            'business_id'    => $businessData['business_id'],
            'location_id' => 0,
            'issue_datetime' => new \DateTime(),
            'items'          => [
                [
                    'product_id' => '123',
                    'name'       => 'Pizza',
                    'quantity'   => 2,
                    'unit_price' => 10,
                ],
                [
                    'product_id' => '15232',
                    'name'       => 'Burger',
                    'quantity'   => 3,
                    'unit_price' => 3,
                ],
            ],
        ];

        $reportingInvoice = $this->zatcaClient->generateInvoiceXML($sampleInvoiceData, $businessData, 'invoice', true);
        $validatedInvoice = $this->zatcaClient->validateInvoiceXml($businessData, $reportingInvoice['utf8Invoice'], $reportingInvoice['invoiceHash'], $sampleInvoiceData['uuid']);
        $reportingInvoice = $this->zatcaClient->generateInvoiceXML($sampleInvoiceData, $businessData, 'credit', true);
        $validatedCredit  = $this->zatcaClient->validateInvoiceXml($businessData, $reportingInvoice['utf8Invoice'], $reportingInvoice['invoiceHash'], $sampleInvoiceData['uuid']);
        $reportingInvoice = $this->zatcaClient->generateInvoiceXML($sampleInvoiceData, $businessData, 'debit', true);
        $validatedDebit   = $this->zatcaClient->validateInvoiceXml($businessData, $reportingInvoice['utf8Invoice'], $reportingInvoice['invoiceHash'], $sampleInvoiceData['uuid']);

        $validations = [
            'invoice' => $validatedInvoice,
            'credit'  => $validatedCredit,
            'debit'   => $validatedDebit,
        ];

        $productionCertificate = $this->zatcaClient->getProductionCertificate($complianceCertificate->getCertificate(), $complianceCertificate->getSecret(), $complianceCertificate->getRequestId());
        if ($productionCertificate) {
            $data['response']['bussiness'] = FastichEgs::create($businessData);
            return redirect()->back()->with('success', 'Business registered successfully');
        }
        return redirect()->back()->with('error', 'Failed to validate invoices');
    }

    public function renewProductionCertificate(Request $request)
    {

        $validated = $request->validate([
            'renewal_otp' => 'required|string|min:6|max:6',
        ]);
        $business_id   = request()->session()->get('user.business_id');
        $bussinessData = FastichEgs::where('business_id', $business_id)->first();
        if ($bussinessData == null) {
            return response()->json(['success' => false, 'error' => 'Business not registered with ZATCA']);
        }
        $certData = $this->zatcaClient->getCertificateData($bussinessData->slug);
        $csr      = $this->zatcaClient->getCsrFromFile();
        $cleanCsr = trim(str_replace(["-----BEGIN CERTIFICATE REQUEST-----", "-----END CERTIFICATE REQUEST-----"], "", $csr));
        try {
            $check = $this->zatcaClient->renewProductionCertificate($certData['certificate'], $certData['secret'], $cleanCsr, $request->renewal_otp);
            if ($check) {
                return response()->json(['success' => true, 'message' => 'Production certificate renewed successfully']);
            }
        } catch (ZatcaApiException $e) {
            $message = $e->getContext()['response']['message'];
            return response()->json(['success' => false, 'error' => $message]);
        }
    }
}
