<?php

namespace App\Services\Zatca\InvoiceCreator;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use App\Services\Zatca\Api\ComplianceCertificateResult;
use App\Services\Zatca\Api\ProductionCertificateResult;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use InvalidArgumentException;
use App\Services\Zatca\Exceptions\ZatcaStorageException;
use Exception;

/**
 * ZATCA E-Invoicing API Client for compliance and reporting operations.
 */
class ZatcaAPI
{
    private const ENVIRONMENTS = [
        'sandbox'    => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal',
        'simulation' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation',
        'production' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core',
    ];

    private const API_VERSION = 'V2';
    private const SUCCESS_STATUS_CODES = [200, 202];

    private ClientInterface $httpClient;
    private bool $allowWarnings = false;
    private string $environment;

    /**
     * @param string $environment API environment (sandbox|simulation|production)
     * @param ClientInterface|null $client Optional HTTP client to enable dependency injection.
     * @throws InvalidArgumentException For invalid environment.
     */
    public function __construct(string $environment = 'sandbox', ?ClientInterface $client = null)
    {
        if (!isset(self::ENVIRONMENTS[$environment])) {
            $validEnvs = implode(', ', array_keys(self::ENVIRONMENTS));
            throw new InvalidArgumentException("Invalid environment. Valid options: $validEnvs");
        }
        $this->environment = $environment;
        $this->httpClient  = $client ?? new Client([
            'base_uri' => $this->getBaseUri(),
            'timeout'  => 30,
            'verify'   => false,
        ]);
    }

    /**
     * Returns the base URI for the current environment.
     *
     * @return string
     */
    public function getBaseUri(): string
    {
        return self::ENVIRONMENTS[$this->environment];
    }

    /**
     * Load CSR file content.
     *
     * @param string $path File path of the CSR.
     * @return string CSR content.
     * @throws \Exception If file is not found or unreadable.
     */
    public function loadCSRFromFile(string $path): string
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \Exception("Could not read file: {$path}");
        }
        return $content;
    }

    /**
     * Enable/disable acceptance of warning responses.
     */
    public function setWarningHandling(bool $allow): void
    {
        $this->allowWarnings = $allow;
    }

    /**
     * Request compliance certificate using CSR and OTP.
     *
     * @param string $csr CSR content.
     * @param string $otp One-Time Password.
     * @return ComplianceCertificateResult
     * @throws ZatcaApiException For API communication errors.
     */
    public function requestComplianceCertificate(string $csr, string $otp): ComplianceCertificateResult
    {
        $response = $this->sendRequest(
            'POST',
            '/compliance',
            ['OTP' => $otp],
            ['csr' => base64_encode($csr)]
        );

        return new ComplianceCertificateResult(
            $response['binarySecurityToken'] ?? '',
            $response['secret'] ?? '',
            $response['requestID'] ?? ''
        );
    }

    /**
     * Validate invoice compliance with ZATCA regulations.
     *
     * @param string $certificate The certificate for authentication.
     * @param string $secret      API secret.
     * @param string $signedInvoice Signed invoice content.
     * @param string $invoiceHash Invoice hash.
     * @param string $uuid      Unique invoice identifier.
     * @return array API response data.
     * @throws ZatcaApiException For API communication errors.
     */
    public function validateInvoiceCompliance(
        string $certificate,
        string $secret,
        string $signedInvoice,
        string $invoiceHash,
        string $uuid
    ): array {
        try {
            return $this->sendRequest(
                'POST',
                '/compliance/invoices',
                ['Accept-Language' => 'en', 'Content-Type' => 'application/json'],
                [
                    'invoiceHash' => $invoiceHash,
                    'uuid'        => $uuid,
                    'invoice'     => base64_encode($signedInvoice),
                ],
                $this->createAuthHeaders($certificate, $secret)
            );
        } catch (ZatcaApiException $e) {
            // يمكن إضافة منطق لمعالجة التحذيرات هنا إذا لزم الأمر.
            throw $e;
        }
    }

    /**
     * Request production certificate using compliance credentials.
     *
     * @param string $certificate         The certificate for authentication.
     * @param string $secret              API secret.
     * @param string $complianceRequestId Compliance request ID.
     * @return ProductionCertificateResult
     * @throws ZatcaApiException For API communication errors.
     */
    public function requestProductionCertificate(
        string $certificate,
        string $secret,
        string $complianceRequestId
    ): ProductionCertificateResult {
        $response = $this->sendRequest(
            'POST',
            '/production/csids',
            ['Content-Type' => 'application/json'],
            ['compliance_request_id' => $complianceRequestId],
            $this->createAuthHeaders($certificate, $secret)
        );

        return new ProductionCertificateResult(
            $response['binarySecurityToken'] ?? '',
            $response['secret'] ?? '',
            $response['requestID'] ?? ''
        );
    }


    /**
     * Request production certificate using compliance credentials.
     *
     * @param string $certificate         The certificate for authentication.
     * @param string $secret              API secret.
     * @param string $csr                 CSR Request in body as Base64.
     * @return ProductionCertificateResult
     * @throws ZatcaApiException For API communication errors.
     */
    public function reRequestProductionCertificate(
        string $certificate,
        string $secret,
        string $csr,
        string $otp
    ): ProductionCertificateResult {
        $response = $this->sendRequest(
            'PATCH',
            '/production/csids',
            ['Content-Type' => 'application/json', 'OTP' => $otp],
            ['csr' => $csr],
            $this->createAuthHeaders($certificate, $secret)
        );

        return new ProductionCertificateResult(
            $response['binarySecurityToken'] ?? '',
            $response['secret'] ?? '',
            $response['requestID'] ?? ''
        );
    }

    /**
     * Submit invoice for clearance reporting.
     *
     * @param string $certificate  The certificate for authentication.
     * @param string $secret       API secret.
     * @param string $signedInvoice Signed invoice content.
     * @param string $invoiceHash  Invoice hash.
     * @param string $egsUuid      Unique invoice identifier.
     * @return array API response data.
     * @throws ZatcaApiException For API communication errors.
     */
    public function submitClearanceInvoice(
        string $certificate,
        string $secret,
        string $signedInvoice,
        string $invoiceHash,
        string $egsUuid
    ): array {
        return $this->sendRequest(
            'POST',
            '/invoices/clearance/single',
            ['Clearance-Status' => config("zatca.CLEARANCE_STATUS"), 'Accept-Language' => 'en'],
            [
                'invoiceHash' => $invoiceHash,
                'uuid'        => $egsUuid,
                'invoice'     => base64_encode($signedInvoice),
            ],
            $this->createAuthHeaders($certificate, $secret)
        );
    }

    /**
     * Submit invoice for reporting.
     *
     * @param string $certificate  The certificate for authentication.
     * @param string $secret       API secret.
     * @param string $signedInvoice Signed invoice content.
     * @param string $invoiceHash  Invoice hash.
     * @param string $egsUuid      Unique invoice identifier.
     * @return array API response data.
     * @throws ZatcaApiException For API communication errors.
     */
    public function submitReportingInvoice(
        string $certificate,
        string $secret,
        string $signedInvoice,
        string $invoiceHash,
        string $egsUuid
    ): array {
        return $this->sendRequest(
            'POST',
            '/invoices/reporting/single',
            ['Clearance-Status' => config("zatca.CLEARANCE_STATUS"), 'Accept-Language' => 'en'],
            [
                'invoiceHash' => $invoiceHash,
                'uuid'        => $egsUuid,
                'invoice'     => $signedInvoice,
            ],
            $this->createAuthHeaders($certificate, $secret)
        );
    }

    /**
     * Generate authentication headers for secured endpoints.
     *
     * @param string $certificate
     * @param string $secret
     * @return array
     */
    private function createAuthHeaders(string $certificate, string $secret): array
    {
        $credentials = base64_encode("$certificate:$secret");
        return ['Authorization' => 'Basic ' . $credentials];
    }

    /**
     * Core request handling with Guzzle.
     *
     * @param string $method HTTP method.
     * @param string $endpoint API endpoint.
     * @param array $headers Additional headers.
     * @param array $payload Request payload.
     * @param array $authHeaders Optional auth headers.
     * @return array Decoded response data.
     * @throws ZatcaApiException On HTTP or API errors.
     */
    private function sendRequest(
        string $method,
        string $endpoint,
        array $headers = [],
        array $payload = [],
        array $authHeaders = []
    ): array {
        try {
            $mergedHeaders = array_merge(
                [
                    'Accept-Version' => self::API_VERSION,
                    'Accept'         => 'application/json',
                ],
                $headers,
                $authHeaders
            );

            $options = [
                'headers' => $mergedHeaders,
                'json'    => $payload,
                'http_errors' => false
            ];
            // استخدام عنوان URL المبني على البيئة الحالية
            $url = $this->getBaseUri() . $endpoint;
            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            if ($this->isSuccessfulResponse($statusCode) == false) {
                $responseData = $this->parseResponse($response);
                
                $errorMessage = implode(', ', array_map(fn($error) => $error['message'], $responseData['errors'] ?? []));
                $errorMessage = $statusCode == 401 ? "Unauthorized request to Zatca API: $endpoint" : $errorMessage;

                if($responseData == null){
                    $responseData = ['message' => 'No response data from Zatca, ask their support for more information.'];
                }
                throw new ZatcaApiException($errorMessage, [
                    'endpoint' => $endpoint,
                    'options'  => $options,
                    'response' => $responseData,
                ], $statusCode);
            }

            return $this->parseResponse($response);
        } catch (GuzzleException $e) {
            // extract response data if available
            throw new ZatcaApiException("An API error has occurred.", [
                'message'  => $e->getMessage(),
                'endpoint' => $endpoint,
            ], $e->getCode(), $e);
        }
    }

    /**
     * Validate HTTP status code against success criteria.
     */
    private function isSuccessfulResponse(int $statusCode): bool
    {
        $status = in_array($statusCode, self::SUCCESS_STATUS_CODES, true) &&
            ($this->allowWarnings || $statusCode === 200);
        return $status;
    }

    /**
     * Parse API response.
     *
     * @param ResponseInterface $response
     */
    private function parseResponse(ResponseInterface $response)
    {
        $content = $response->getBody()->getContents();
        $data    = json_decode($content, true);
        return $data;
    }

    /**
     * Format certificate string with PEM boundaries.
     *
     * @param string $base64Certificate
     * @return string
     */
    private function formatCertificate(string $base64Certificate): string
    {
        return base64_decode($base64Certificate);
    }

    /**
     * Save certificate data to a JSON file.
     *
     * @param string $certificate The certificate string.
     * @param string $secret      The API secret.
     * @param string $requestId   The request ID.
     * @param string $filePath    Path to save the JSON file.
     * @return void
     * @throws \Exception If failed to encode data to JSON
     * @throws ZatcaStorageException If file cannot be written.
     */
    public function saveToJson(string $certificate, string $secret, string $requestId, string $filePath): void
    {
        $data = [
            'certificate' => $certificate,
            'secret'      => $secret,
            'requestId'   => $requestId,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \Exception("Failed to encode data to JSON: " . json_last_error_msg());
        }

        (new Storage)->put($filePath, $json);
    }
}
