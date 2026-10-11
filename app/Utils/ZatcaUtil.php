<?php

namespace App\Utils;

use App\Models\FastichEgs;
use App\Models\ZatcaInvoiceHistory;
use App\Services\Zatca\Api\ComplianceCertificateResult;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use App\Services\Zatca\Helpers\Certificate;
use App\Services\Zatca\Helpers\InvoiceExtension;
use App\Services\Zatca\Helpers\QRCodeGenerator;
use App\Services\Zatca\InvoiceCreator\{
    AdditionalDocumentReference,
    Address,
    Attachment,
    BillingReference,
    ZatcaAPI,
    AllowanceCharge,
    ClassifiedTaxCategory,
    Delivery,
    ExtensionContent,
    GeneratorInvoice,
    Invoice,
    InvoiceLine,
    InvoiceSigner,
    InvoiceType,
    Item,
    LegalEntity,
    LegalMonetaryTotal,
    Party,
    PartyTaxScheme,
    PaymentMeans,
    Price,
    Signature,
    SignatureInformation,
    TaxCategory,
    TaxScheme,
    TaxSubTotal,
    TaxTotal,
    UBLDocumentSignatures,
    UBLExtension,
    UBLExtensions,
    UnitCode
};
use App\Transaction;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ZatcaUtil
{
    public ZatcaAPI $zatcaClient;
    private string $basePath;
    private ?string $currentBusinessPath = null;

    public function __construct()
    {
        $env = env('ZATCA_PRODUCTION', false) == 1 ? true : false;
        $z = new ZatcaAPI($env == true ? 'production' : 'sandbox');
        $z->setWarningHandling(true);
        $this->zatcaClient = $z;
        $this->basePath = storage_path('uploads/zatca_credentials/');
    }

    /**
     * Set current business context for file operations
     */
    private function setBusinessContext(string $slug): void
    {
        $this->currentBusinessPath = $this->basePath . $slug . '/';
        if (!is_dir($this->currentBusinessPath)) {
            // Create directory if it does not exist
            // fix path
            $this->currentBusinessPath = str_replace("\\", "/", $this->currentBusinessPath);
            mkdir($this->currentBusinessPath, 0775, true);
        }
    }

    /**
     * Get full path for a file in the current business context
     */
    private function getPath(string $filename): string
    {
        if (!$this->currentBusinessPath) {
            throw new RuntimeException("Business context not set");
        }
        return $this->currentBusinessPath . $filename;
    }

    public function getCsrFromFile(): string
    {
        $csrPath = $this->getPath('certificate.csr');
        $csrFile = fopen($csrPath, 'r');
        $csr = fread($csrFile, filesize($csrPath));
        fclose($csrFile);
        return $csr;
    }



    /**
     * Convert business data to configuration format
     */
    private function businessDataToConfigurationData($businessData): array
    {
        return [
            'businessId' => $businessData['business_id'],
            'commonName' => $businessData['common_name'],
            'country' => 'SA',
            'organizationName' => $businessData['registered_business_name'],
            'organizationalUnitName' => $businessData['registered_business_name'],
            'solutionName' => 'POS',
            'egs_id' => $businessData['egs_id'],
            'uuid' => $businessData['uuid'],
            'crnNumber' => $businessData['crn_number'],
            'vatNumber' => $businessData['vat_number'],
            'invoiceType' => '0100',
            'registeredAddress' => $businessData['registered_address'],
            'businessCategory' => $businessData['business_category'],
            'serialNumber' => '1-POS' . '|2-' . $businessData['egs_id'] . '|3-' . $businessData['uuid'],
            "address" => [
                'streetName' => $businessData['street_name'],
                'buildingNumber' => $businessData['building_number'],
                'citySubdivisionName' => $businessData['city_subdivision_name'],
                'cityName' => $businessData['city_name'],
                'postalZone' => $businessData['postal_zone'],
                'country' => 'SA',
            ],
        ];
    }

    /**
     * Genrates Public & Private Keys for the first time use
     * @param void
     * @return array the private key `$privateKey` and the public key `$publicKey` inside an array, in addition to the private key path `$privateKeyPath`
     */
    public function generateKeys($privateKeyPath): array
    {
        $privateKeySSL = openssl_pkey_new(config("zatca.PRIVATE_KEY_OPTIONS")); // PRIVATE_KEY_OPTIONS is in const.php
        openssl_pkey_export($privateKeySSL, $privateKeyStr);
        $details = openssl_pkey_get_details($privateKeySSL);
        $publicKey = $details['key'];
        $privateKeyFile = fopen($privateKeyPath, 'w');
        fwrite($privateKeyFile, str_replace("PRIVATE", "EC PRIVATE", $privateKeyStr));
        fclose($privateKeyFile);
        return [
            'privateKey' => $privateKeyStr,
            'privateKeyPath' => $privateKeyPath,
            'publicKey' => $publicKey,
            'privateKeySSL' => $privateKeySSL
        ];
    }

    /**
     * Creates a `config.cnf` file based on a data array
     * @param array $data The data included in array with keys `$emailAddress`, `$commonName`, `$country`, `$organizationalUnitName`, `$organizationName`, `$serialNumber`, `$vatNumber`, `$invoiceType`, `registeredAddress` & `businessCategory`
     * @param string $output the output file name (without extension)
     * @return array [
     * 
     * `data` => the file content as a string
     * 
     * `$path` => the `config.cnf` file path
     * 
     * ]
     */
    public function createConfigCnf(array $data, $csrConfigPath): array
    {
        $template = config("zatca.CONFIG_CNF_FILE_TEMPLATE");
        foreach ($data as $key => $value) {
            // if the value is an array, skip it
            if (is_array($value)) {
                continue;
            }
            $configCnf = str_replace("__$key", $value, $template);
            $template = $configCnf;
        }
        $configCnfFile = fopen($csrConfigPath, 'w');
        fwrite($configCnfFile, $configCnf);
        fclose($configCnfFile);
        return ['data' => $configCnf, 'path' => $csrConfigPath];
    }

    /**
     * Creates a Certificate Signing Request based on a private key and a configuration file
     * @param string $privateKeyPath The path to the stored private key
     * @param string $configCnfPath The path to `.cnf` file
     * @param string $outputFileName The desired name for the output csr file, without extension. Default set to `taxpayer`
     * @return array [
     * 
     * `utf8` => The csr string decoded in utf8
     * 
     * `base64` => The csr string encoded to base64
     * 
     * ]
     */
    public function createCsr(string $privateKeyPath, string $configCnfPath, string $csrPath): array
    {
        // Run the openssl command to create the CSR file
        $command = "openssl req -new -sha256 -key \"$privateKeyPath\" -extensions v3_req -config \"$configCnfPath\" -out \"$csrPath\"";
        $output = shell_exec($command . " 2>&1");
        $exitCode = shell_exec("echo $?");

        // Check if the command failed
        if ($exitCode != 0) {
            Log::error("OpenSSL command failed with exit code $exitCode: $output");
            throw new RuntimeException("Failed to create CSR file. OpenSSL command failed.");
        }

        // Check if the CSR file was created successfully
        if (!file_exists($csrPath)) {
            Log::error("CSR file not found: $csrPath");
            throw new RuntimeException("Failed to create CSR file. File not found.");
        }

        // Read the CSR file
        $csrFile = fopen($csrPath, 'r');
        $csr = fread($csrFile, filesize($csrPath));
        $csrEncoded = base64_encode($csr);
        fclose($csrFile);

        return ['utf8' => $csr, 'base64' => $csrEncoded];
    }

    /**
     * Generate certificate for a business
     */
    public function generateCertificate($businessData,  $otp): ComplianceCertificateResult
    {
        try {
            $this->setBusinessContext($businessData['slug']);
            $businessData = $this->businessDataToConfigurationData($businessData);
            $csrConfigPath = $this->getPath('config.cnf');
            $csrPath = $this->getPath('certificate.csr');
            $privateKeyPath = $this->getPath('private.pem');
            $keys = $this->generateKeys($privateKeyPath);
            $this->createConfigCnf($businessData, $csrConfigPath);
            $csr = $this->createCsr($privateKeyPath, $csrConfigPath, $csrPath);
            $complianceResult = $this->requestComplianceCertificate(
                $csr['utf8'],
                $otp
            );
            return $complianceResult;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Request compliance certificate from ZATCA
     */
    public function requestComplianceCertificate(string $csr, string $otp): ComplianceCertificateResult
    {
        try {
            $complianceResult = $this->zatcaClient->requestComplianceCertificate($csr, $otp);
            $json = [
                'compliance' => [
                    'certificate' => $complianceResult->getCertificate(),
                    'secret' => $complianceResult->getSecret(),
                    'requestId' => $complianceResult->getRequestId()
                ],
            ];
            if (!file_exists($this->getPath('ZATCA_certificate_data.json'))) {
                $file = fopen($this->getPath('ZATCA_certificate_data.json'), "w");
                fwrite($file, json_encode([], JSON_THROW_ON_ERROR));
                fclose($file);
            }
            $file = fopen($this->getPath('ZATCA_certificate_data.json'), "r");
            $content = fread($file, filesize($this->getPath('ZATCA_certificate_data.json')));
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            fclose($file);
            $content = array_merge($content, $json);
            $file = fopen($this->getPath('ZATCA_certificate_data.json'), "w");
            fwrite($file, json_encode($content, JSON_THROW_ON_ERROR));
            fclose($file);
            return $complianceResult;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Function to get production certificate
     * @param string $certificate         The certificate for authentication.
     * @param string $secret              API secret.
     * @param string $complianceRequestId Compliance request ID.
     */
    public function getProductionCertificate(string $certificate, string $secret, string $complianceRequestId): bool
    {
        try {
            $productionResult = $this->zatcaClient->requestProductionCertificate($certificate, $secret, $complianceRequestId);
            $json = [
                'production' => [
                    'certificate' => $productionResult->getCertificate(),
                    'secret' => $productionResult->getSecret(),
                    'requestId' => $productionResult->getRequestId()
                ]
            ];
            if (!file_exists($this->getPath('ZATCA_certificate_data.json'))) {
                $file = fopen($this->getPath('ZATCA_certificate_data.json'), "w");
                fwrite($file, json_encode([], JSON_THROW_ON_ERROR));
                fclose($file);
            }
            $file = fopen($this->getPath('ZATCA_certificate_data.json'), "r");
            $content = fread($file, filesize($this->getPath('ZATCA_certificate_data.json')));
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            fclose($file);
            $content = array_merge($content, $json);
            $file = fopen($this->getPath('ZATCA_certificate_data.json'), "w");
            fwrite($file, json_encode($content, JSON_THROW_ON_ERROR));
            fclose($file);
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Function to get production certificate
     * @param string $certificate         The certificate for authentication.
     * @param string $secret              API secret.
     * @param string $complianceRequestId Compliance request ID.
     */
    public function renewProductionCertificate(string $certificate, string $secret, string $csr, $otp): bool
    {
        try {
            $productionResult = $this->zatcaClient->reRequestProductionCertificate($certificate, $secret, $csr, $otp);
            $json = [
                'production' => [
                    'certificate' => $productionResult->getCertificate(),
                    'secret' => $productionResult->getSecret(),
                    'requestId' => $productionResult->getRequestId()
                ]
            ];
            if (!file_exists($this->getPath('ZATCA_certificate_data.json'))) {
                $file = fopen($this->getPath('ZATCA_certificate_data.json'), "w");
                fwrite($file, json_encode([], JSON_THROW_ON_ERROR));
                fclose($file);
            }
            $file = fopen($this->getPath('ZATCA_certificate_data.json'), "r");
            $content = fread($file, filesize($this->getPath('ZATCA_certificate_data.json')));
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            fclose($file);
            $content = array_merge($content, $json);
            $file = fopen($this->getPath('ZATCA_certificate_data.json'), "w");
            fwrite($file, json_encode($content, JSON_THROW_ON_ERROR));
            fclose($file);
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * Save XML invoice
     */
    public function saveInvoiceXml(string $slug, string $xml, string $name): string
    {
        $this->setBusinessContext($slug);
        $path = $this->getPath($name);
        file_put_contents($path, $xml);
        return $this->getPath($path);
    }

    /**
     * Get signed XML invoice from file
     */
    public function getSignedXmlInvoice(string $slug, string $name): string
    {
        $this->setBusinessContext($slug);
        $path = $this->getPath($name);
        return file_get_contents($path);
    }

    /**
     * Get stored certificate data
     */
    public function getCertificateData(string $slug, $forCompliance = false): ?array
    {
        $this->setBusinessContext($slug);
        $path = $this->getPath('ZATCA_certificate_data.json');
        // open the file
        $file = fopen($path, "r");
        // read the file
        $content = fread($file, filesize($path));
        $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $content = $forCompliance == true ? $content['compliance'] : $content['production'];
        // close the file
        fclose($file);
        return $content ?? null;
    }


    /**
     * Clean up business files
     */
    public function cleanupBusinessFiles(string $slug): bool
    {
        $this->setBusinessContext($slug);
        return Storage::deleteDirectory($this->currentBusinessPath);
    }

    public function generateZatcaInvoiceWithQrCode($transaction_id, $retry = 0)
    {
        try {
            // Fetch the transaction with sell_lines and their related product data
            $transaction = Transaction::with(['business', 'location', 'tax', 'zatca_invoice_history', 'sell_lines.product'])->where("is_suspend", "=", 0)->find($transaction_id);
            if ($transaction == null) {
                throw new RuntimeException("Transaction not found");
            }
            if ($transaction->type == 'sell' && $transaction->sub_type == 'debit' && $transaction->status == 'final' && in_array($transaction->payment_status, ['paid', 'partial'])) {
                $type = 'debit';
            } elseif($transaction->type == 'sell' && $transaction->status == 'final' && in_array($transaction->payment_status, ['paid', 'partial'])) {
                $type = 'invoice';
            } elseif ($transaction->type == 'sell' && $transaction->status == 'final' && $transaction->payment_status == 'returned') {
                $type = 'credit';
            } else {
                throw new RuntimeException("Invalid transaction type");
            }
            // if history is already created, and type is same as the current type, return the history
            if (isset($transaction->zatca_invoice_history) && $transaction->zatca_invoice_history->invoice_type == $type && $retry == 0) {
                return ["qrCode" => $transaction->generatedBase64String];
            }
            if ($transaction->location->is_active == 0) {
                throw new RuntimeException("Location is inactive");
            }

            // Get business data from Fastich_egs
            $business = FastichEgs::where('business_id', $transaction->business_id)->first();
            if ($business == null) {
                throw new RuntimeException("Business not found");
            }


            if ($transaction->total_before_tax > 0) {
                $taxRate = ($transaction->tax_amount / $transaction->total_before_tax) * 100;
            }
            $taxRate = round($transaction->tax->amount, 2);
            // Process each sell line into invoice items
            $processedItems = [];
            foreach ($transaction->sell_lines as $sellLine) {
                $unitPrice = (float) $sellLine->unit_price;
                $quantity = (float) $sellLine->quantity;

                $totalPriceBeforeTax = $unitPrice * $quantity;
                $itemTaxAmount = $totalPriceBeforeTax * ($taxRate / 100);
                $totalAmountWithTax = $totalPriceBeforeTax + $itemTaxAmount;

                $processedItems[] = [
                    'name' => $sellLine->product->name ?? 'Unknown Product', // Ensure product name is available
                    'unit_price' => round($unitPrice, 2),
                    'quantity' => $quantity,
                    'total_price_before_tax' => round($totalPriceBeforeTax, 2),
                    'tax_amount' => round($itemTaxAmount, 2),
                    'total_amount_with_tax' => round($totalAmountWithTax, 2),
                ];
            }

            // Retrieve previous invoice details for PIh and ICV
            $egsRecord = ZatcaInvoiceHistory::getLastBusinessInvoice($business->business_id, $transaction->location_id);
            $pih = $egsRecord->ih ?? base64_encode(random_bytes(32));
            $icv = $egsRecord ? (int) $egsRecord->icv + 1 : 1;

            $uuid = Str::uuid();
            $irn = $this->generateIRN($uuid, $transaction->invoice_no, $transaction->transaction_date);
            $d = new \DateTime($transaction->transaction_date);
            $maxRetries = 5;
            $retryCount = 0;
            $saved = false;
            $data = [];
            while (!$saved && $retryCount < $maxRetries) {
                try {
                    // Prepare invoice data structure
                    $invoiceData = [
                        'icv' => $icv,
                        'invoice_no' => $transaction->invoice_no,
                        'credit_no' => $transaction->credit_no,
                        'debit_no' => $transaction->debit_no,
                        'pih' => $pih,
                        'tax_rate' => $taxRate,
                        'business_id' => $transaction->business_id,
                        'location_id' => $transaction->location_id,
                        'items' => $processedItems,
                        'uuid' => $uuid,
                        'issue_datetime' => $d,
                        'irn' => $irn,
                        'summary' => [
                            'total_price_before_tax' => round($transaction->total_before_tax, 2),
                            'total_tax_amount' => round($transaction->tax_amount, 2),
                            'total_amount_with_tax' => round($transaction->final_total, 2),
                        ],
                    ];

                    $businessData = $business->toArray();
                    // Generate the XML invoice
                    $data = $this->generateInvoiceXML($invoiceData, $businessData, $type);

                    //validate the invoice
                    //$validatedResult = $this->validateInvoiceXml($businessData, $data['utf8Invoice'], $data['invoiceHash'], $invoiceData['uuid']);
                    // if zatca_invoice_history is not created, create it and otherwise update it
                    // Retry mechanism for unique constraint error

                    $zih = [
                        'validation_status' => 'FAIL',
                        'reporting_status' => 'NOT_REPORTED',
                        'invoice_number' => $transaction->invoice_no,
                        'total_amount' => $transaction->final_total,
                        'invoice_date' => $transaction->transaction_date,
                        "icv" => $icv,
                        "pih" => $pih,
                        "location_id" => $transaction->location_id,
                        "uuid" => $invoiceData['uuid'],
                        "transaction_id" => $transaction->id,
                        "validation_response" => "",
                        "irn" => $irn,
                        "invoice_type" => $type,
                        "ih" => $data['invoiceHash']
                    ];
                    if (isset($transaction->zatca_invoice_history)) {
                        // if zatca icv and zih icv is same remove from zih
                        if ($transaction->zatca_invoice_history->icv == $icv) {
                            unset($zih['icv']);
                        }
                        ZatcaInvoiceHistory::where('transaction_id', $transaction->id)->update($zih);
                    } else {
                        ZatcaInvoiceHistory::create(
                            array_merge($zih, ['business_id' => $transaction->business_id])
                        );
                    }
                    $saved = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                        $egsRecord = ZatcaInvoiceHistory::getLastBusinessInvoice($business->business_id, $transaction->location_id);
                        $pih = $egsRecord->ih ?? base64_encode(random_bytes(32));
                        $icv = $egsRecord ? (int) $egsRecord->icv + 1 : 1;
                        $retryCount++;
                    } else {
                        throw $e;
                    }
                }
            }

            // update transaction table set generatedBase64String as qrCode
            $transaction->generatedBase64String = $data['qrCode'];
            Transaction::where('id', $transaction->id)->update(['generatedBase64String' => $data['qrCode']]);

            if (!$saved) {
                throw new RuntimeException("Failed to save invoice after $maxRetries retries.");
            }

            return $data;
        } catch (\Exception $e) {
            Log::error($e);
            return "";
        }
    }
    /**
     * Generate an XML invoice
     * @param array $invoiceData The invoice data
     * @param array $businessData The business data
     * @param string $type The invoice type (invoice, credit, debit)
     * @param bool $forCompliance Whether the invoice is for compliance or not
     * @return array [
     *  `utf8Invoice` => The signed xml document as a string,
     *  `invoice` => The signed xml document encoded to base64,
     *  `invoiceHash` => The signed invoice hash,
     *  `qrCode` => The QR code for the invoice
     * ] | Exception if an error occurs
     */
    function generateInvoiceXML(array $invoiceData, array $businessData, string $type = 'invoice', $forCompliance = false): array
    {
        // Validate required fields
        $requiredKeys = ['icv', 'invoice_no', 'pih', 'tax_rate', 'business_id', 'items', 'uuid'];
        foreach ($requiredKeys as $key) {
            if (!isset($invoiceData[$key])) {
                throw new InvalidArgumentException("Missing required key: $key");
            }
        }

        // Calculate totals from items
        $lineExtensionAmount = 0;
        $totalTaxAmount = 0;
        $invoiceLines = [];
        $taxRate = $invoiceData['tax_rate'];
        $taxCategoryCode = $taxRate == 0 ? 'Z' : 'S'; // Z=Zero rated, S=Standard rate
        $taxExemptionReason = $taxRate == 0 ? 'VATEX-SA-29' : null; // Required for zero-rated

        foreach ($invoiceData['items'] as $index => $item) {
            $quantity = $item['quantity'];
            $unitPrice = $item['unit_price'];
            $lineTotal = $quantity * $unitPrice;
            $taxAmount = $lineTotal * ($taxRate / 100);

            $lineExtensionAmount += $lineTotal;
            $totalTaxAmount += $taxAmount;

            // Create tax components
            $taxScheme = (new TaxScheme())->setId("VAT");
            $classifiedTax = (new ClassifiedTaxCategory())
                ->setId($taxCategoryCode)
                ->setPercent($taxRate)
                ->setTaxScheme($taxScheme)
                ->setTaxExemptionReason($taxExemptionReason);


            // Create item components
            $productItem = (new Item())
                ->setName($item['name'])
                ->setClassifiedTaxCategory($classifiedTax);

            $price = (new Price())
                ->setUnitCode(UnitCode::UNIT)
                ->setPriceAmount($unitPrice);

            $lineTaxTotal = (new TaxTotal())
                ->setTaxAmount($taxAmount)
                ->setRoundingAmount($lineTotal + $taxAmount);

            $invoiceLines[] = (new InvoiceLine())
                ->setUnitCode("PCE")
                ->setId($index + 1)
                ->setItem($productItem)
                ->setLineExtensionAmount($lineTotal)
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setInvoicedQuantity($quantity);
        }

        // Calculate monetary totals
        $taxExclusiveAmount = $lineExtensionAmount;
        $taxInclusiveAmount = $lineExtensionAmount + $totalTaxAmount;

        // --- Signature & Extension Components ---
        $signatureInfo = (new SignatureInformation)
            ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setID('urn:oasis:names:specification:ubl:signature:1');

        $ublDocSignatures = (new UBLDocumentSignatures)->setSignatureInformation($signatureInfo);
        $extensionContent = (new ExtensionContent)->setUBLDocumentSignatures($ublDocSignatures);

        $ublExtensions = (new UBLExtensions)
            ->setUBLExtensions([(new UBLExtension)
                ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
                ->setExtensionContent($extensionContent)]);

        // --- Document References ---
        $additionalDocs = [
            (new AdditionalDocumentReference())->setId('ICV')->setUUID((string)$invoiceData['icv']),
            (new AdditionalDocumentReference())->setId('IRN')
                ->setAttachment((new Attachment())
                    ->setBase64Content(base64_encode($invoiceData['irn'] ?? $invoiceData['invoice_no']), 'base64', 'text/plain')),
            (new AdditionalDocumentReference())->setId('PIH')
                ->setAttachment((new Attachment())
                    ->setBase64Content($invoiceData['pih'], 'base64', 'text/plain')),
        ];


        // --- Tax Components ---
        $taxScheme = (new TaxScheme())->setId("VAT");
        $taxCategory = (new TaxCategory())->setId($taxCategoryCode)->setPercent($taxRate)->setTaxScheme($taxScheme);

        // --- Address & Legal Entity (Customize these as needed) ---
        $address = (new Address())
            ->setStreetName($businessData['street_name'])
            ->setBuildingNumber($businessData['building_number'])
            ->setCitySubdivisionName($businessData['city_subdivision_name'])
            ->setCityName($businessData['city_name'])
            ->setPostalZone($businessData['postal_zone'])
            ->setCountry('SA');

        $legalEntity = (new LegalEntity())->setRegistrationName($businessData['registered_business_name']);
        $d = $invoiceData['issue_datetime'];
        // --- Build the Invoice ---
        $invoice = (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID($invoiceData['uuid'])
            ->setId($invoiceData['invoice_no'])
            ->setIssueDate($d)
            ->setIssueTime($d)
            ->setInvoiceType((new InvoiceType())
                ->setInvoice('simplified')
                ->setInvoiceType($type)
                ->setIsThirdParty(false)
                ->setIsNominal(false)
                ->setIsExportInvoice(false)
                ->setIsSummary(false)
                ->setIsSelfBilled(false))
            ->setInvoiceCurrencyCode('SAR')
            ->setTaxCurrencyCode('SAR')
            ->setAdditionalDocumentReferences($additionalDocs)
            ->setAccountingSupplierParty((new Party())
                ->setPartyIdentification($businessData['crn_number'])
                ->setPartyIdentificationId('CRN')
                ->setLegalEntity($legalEntity)
                ->setPostalAddress($address)
                ->setPartyTaxScheme((new PartyTaxScheme())
                    ->setTaxScheme($taxScheme)
                    ->setCompanyId($businessData['vat_number'])))
            ->setAccountingCustomerParty((new Party())
                ->setPartyIdentification($businessData['vat_number'])
                ->setPartyIdentificationId("NAT")
                ->setLegalEntity($legalEntity)
                ->setPostalAddress($address))
            ->setDelivery((new Delivery())->setActualDeliveryDate(date('Y-m-d')))
            ->setTaxTotal((new TaxTotal())
                ->addTaxSubTotal((new TaxSubTotal())
                    ->setTaxableAmount($lineExtensionAmount)
                    ->setTaxAmount($totalTaxAmount)
                    ->setTaxCategory($taxCategory))
                ->setTaxAmount($totalTaxAmount))
            ->setLegalMonetaryTotal((new LegalMonetaryTotal())
                ->setLineExtensionAmount($lineExtensionAmount)
                ->setTaxExclusiveAmount($taxExclusiveAmount)
                ->setTaxInclusiveAmount($taxInclusiveAmount)
                ->setPayableAmount($taxInclusiveAmount))
            ->setInvoiceLines($invoiceLines)
            ->setSignature((new Signature())
                ->setId("urn:oasis:names:specification:ubl:signature:Invoice")
                ->setSignatureMethod("urn:oasis:names:specification:ubl:dsig:enveloped:xades"))
            ->setAllowanceCharges([
                (new AllowanceCharge())
                    ->setChargeIndicator(false)
                    ->setAllowanceChargeReason('discount')
                    ->setAmount(0.00)
                    ->setTaxCategory($taxCategory)
            ]);
        if (in_array($type, ['credit', 'debit'])) {
            $reason = "CANCELLATION_OR_TERMINATION";
            $invoice->setNote($reason)
                ->setPaymentMeans((new PaymentMeans())->setInstructionNote($reason)->setPaymentMeansCode("10"))
                ->setBillingReferences([(new BillingReference())
                    ->setId($invoiceData['invoice_no'])]);
        }

        try {
            $generatorXml = GeneratorInvoice::invoice($invoice);
            $unsignedXml = $generatorXml->getXML();
            $name = $type . '_' . $invoiceData['invoice_no'] . '_' . $invoiceData['business_id'] . '_' . $invoiceData['location_id'];
            $this->setBusinessContext($businessData['slug']);
            $this->saveInvoiceXml($businessData['slug'], $unsignedXml, $name . '.xml');
            $signed = $this->signXmlInvoice($this->getPath($name . '.xml'), $this->getPath('private.pem'), $name . '_signed.xml');
            $this->saveInvoiceXml($businessData['slug'],  $signed['utf8Invoice'], $name . '_signed.xml');
            
            //log::error($this->getPath('private.pem'));
            

            $qrCode = $this->generateQrCode($signed['utf8Invoice'], $businessData['slug'], $forCompliance);
            $data = array_merge($signed, ['qrCode' => $qrCode, 'uuid' => $invoiceData['uuid']]);
            return $data;
        } catch (\Exception $e) {
             
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            throw new Exception("XML Generation Error: " . $e->getMessage());
        }
    }

    function generateIRN($invoiceUUID, $invoiceNo, $createdAt)
    {
        $timestamp = date("YmdHis", strtotime($createdAt));
        $uuidHash = substr(hash('sha256', $invoiceUUID), 0, 10);
        $irn = $invoiceNo . $timestamp . $uuidHash;
        $irn = str_pad($irn, 22, '0', STR_PAD_LEFT);
        return $irn;
    }


    /**
     * Signs an XML E-invoice and return the signed invoice (both encoded and decoded) in addition to the signed invoice hash
     * @param string $xmlInvoicePath The path to the xml invoice document
     * @param string $privateKeyPath The path to the private key
     * @return array [
     * `utf8Invoice` => The signed xml document as a string,
     * 
     * `invoice` => The signed xml document encoded to base64,
     * 
     * `invoiceHash` => The signed invoice hash
     * ]
     */
    public function signXmlInvoice(string $xmlInvoicePath, string $privateKeyPath, $name): array
    {
        try {
            // fix path
            $signedInvoicePath = $this->getPath($name);
            $xmlInvoicePath = str_replace("\\", "/", $xmlInvoicePath);
            $privateKeyPath = str_replace("\\", "/", $privateKeyPath);
            $signedInvoicePath = str_replace("\\", "/", $signedInvoicePath);
            $csrConfigPath = $this->getPath('config.cnf');
            $generatedCsr = $this->getPath('certificate.csr');
            // Prepare the shell command
            $JAVA_HOME = env('JAVA_HOME');
            $FATOORA_HOME = env('FATOORA_HOME');
            $SDK_CONFIG = env('SDK_CONFIG');
            $PUBLIC_HTML = env('PUBLIC_HTML');
            $command = "
            export PATH=\"\" &&
            export JAVA_HOME=$JAVA_HOME && 
            export PATH=\$JAVA_HOME/bin:\$PATH && 
            export FATOORA_HOME=$FATOORA_HOME && 
            export SDK_CONFIG=$SDK_CONFIG && 
            export PATH=\$FATOORA_HOME:\$PATH && 
            fatoora -sign -qr -invoice \"$xmlInvoicePath\" -privateKey \"$privateKeyPath\" -signedInvoice \"$signedInvoicePath\"";

            Log::error($command);
            $IS_DEV = env('IS_DEV', 0);
            if ($IS_DEV == 1) {
                $command = "fatoora -sign -qr -invoice \"$xmlInvoicePath\" -privateKey \"$privateKeyPath\" -signedInvoice \"$signedInvoicePath\"";
            }
           
            // Run the shell command
            $cmd = shell_exec($command . " 2>&1");
            Log::error($cmd);

            $errorOutput = shell_exec("echo $?");
            // Check if the command failed
            if ($cmd === null) {
                Log::error("Shell Exec failed with error code: $errorOutput");
            }

            // Extract the invoice hash from the command output
            $invoiceHash = explode("INVOICE HASH = ", $cmd)[1];
            $invoiceHash = str_replace("\n", "", $invoiceHash);

            // Read the signed invoice file
            try {
                $signedInvoiceFile = fopen($signedInvoicePath, 'r');
            } catch (\Exception $e) {
                // fatoora has placed file in public_html
                $public = $PUBLIC_HTML . $name;
                rename($public, $signedInvoicePath);
                $signedInvoiceFile = fopen($signedInvoicePath, 'r');
                // move 
            }
            $signedInvoice = fread($signedInvoiceFile, filesize($signedInvoicePath));
            $encodedSignedInvoice = base64_encode($signedInvoice);
            fclose($signedInvoiceFile);

            return [
                "utf8Invoice" => $signedInvoice,
                "invoice" => $encodedSignedInvoice,
                "invoiceHash" => $invoiceHash,
                "status" => true
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            throw new Exception("XML Signing Error: " . $e->getMessage());
        }
    }

    /**
     * Generate QR code for an invoice
     * @param string $xmlInvoice The Signed XML invoice content
     * @param string $businessId The business ID
     * @param string $businessName The business name
     * @param bool $forCompliance Whether the invoice is for compliance or not
     * @return string The base64 encoded QR code
     */

    public function generateQrCode($xmlInvoice, $slug, $forCompliance = false): string
    {
        $data = $this->getCertificateData($slug, $forCompliance);
        $privateKey = file_get_contents($this->getPath('private.pem'));
        $cleanPrivateKey = trim(str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], "", $privateKey));
        $certificate = new Certificate(base64_decode($data['certificate']), $cleanPrivateKey, $data['secret']);
        $xmlDom = InvoiceExtension::fromString($xmlInvoice);
        $invoiceHashBinary = hash('sha256', $xmlDom->getElement()->C14N(false, false), true);
        $hash = base64_encode($invoiceHashBinary);
        // Create digital signature using the private key
        $digitalSignature = base64_encode(
            $certificate->getPrivateKey()->sign($invoiceHashBinary)
        );
        $qrCode = QRCodeGenerator::createFromTags(
            $xmlDom->generateQrTagsArray($certificate, $hash, $digitalSignature)
        )->encodeBase64();
        $qrCode = (new QRCode(new QROptions()))->render($qrCode);
        return $qrCode;
    }

    /**
     * Validate invoice XML
     */
    /**
     * Validate invoice XML with ZATCA
     *
     * @param string $signedInvoice The signed XML invoice content
     * @param string $invoiceHash The hash of the invoice
     * @param string $uuid The UUID of the invoice
     * @return array Response with validation status and any errors
     */
    public function validateInvoiceXml(array $businessData, string $signedInvoice, string $invoiceHash, string $uuid): array
    {
        try {
            if (!$this->currentBusinessPath) {
                throw new RuntimeException("Business context not set");
            }

            $certData = $this->getCertificateData($businessData['slug'], true);


            if (!$certData) {
                throw new RuntimeException("Certificate data not found");
            }

            $secret = $certData['secret'] ?? null;
            if (!$secret) {
                throw new RuntimeException("Certificate secret not found");
            }

            $validationResult = $this->zatcaClient->validateInvoiceCompliance(
                $certData['certificate'],
                $secret,
                $signedInvoice,
                $invoiceHash,
                $uuid
            );

            // Check validation result
            $status = $validationResult['validationResults']['status'] ?? null;
            $warnings = $validationResult['validationResults']['warnings'] ?? [];
            $errors = $validationResult['validationResults']['errors'] ?? [];

            return [
                'success' => $status === 'PASS',
                'status' => $status,
                'warnings' => $warnings,
                'errors' => $errors,
                'validationResults' => $validationResult['validationResults'] ?? [],
                'reportingStatus' => $validationResult['reportingStatus'] ?? null
            ];
        } catch (ZatcaApiException $e) {
            return [
                'success' => false,
                'status' => 'FAIL',
                'errors' => [$e->getContext()['response']],
                'validationResults' => $e->getContext()['response']
            ];
        }
    }
}
