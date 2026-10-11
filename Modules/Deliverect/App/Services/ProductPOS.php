<?php

namespace Modules\Deliverect\App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Deliverect\App\Models\DeliverectRegistration;

class ProductPOS
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }



    public function sync_products(array $data, $business_id, $pos_location)
    {
        Log::info('Sync Started for Bussiness: ' . $business_id);

        $auth = $this->authService->authenticate_request($business_id);

        if (!$auth) {
            return [
                'success' => false,
                'message' => 'Unauthorized request or business not found',
            ];
        }
        // if $data does not contain products log the error and return
        if (!isset($data['products'])) {
            Log::info('Request Response: ' . 'Products not found');
            return [
                'success' => false,
                'message' => 'Products not found',
            ];
        }

        $products = $data['products'];
        $priceLevels = $data['priceLevels'];
        $categories = $data['categories'];

        try {
            $registration = DeliverectRegistration::where('business_id', $business_id)->where('pos_location', $pos_location)->first();
            Log::info('Request Response: ' . $registration);
            
            if ($registration) {
                $client = new Client();
                $response = $client->request('POST', $this->authService->DeliverectURL() . '/productAndCategories?forceUpdate=true', [
                    'json' => [
                        'accountId' => $registration->account_id,
                        'locationId' => $registration->location_id,
                        'products' => $products,
                        'categories' => $categories,
                        'priceLevels' => $priceLevels,
                    ],
                    'headers' => [
                        'authorization' => $auth->token_type . ' ' . $auth->access_token,
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                    ],
                    'verify' => false
                ]);
                $statusCode = $response->getStatusCode();
                Log::info('Request Response: ' . $response->getBody()->getContents());
                if ($statusCode === 200) {
                    return [
                        'success' => true,
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Unauthorized request or business not found',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Unauthorized request or business not found',
                    "data" => []
                ];
            }
        } catch (RequestException $e) {
            Log::info('Request Response: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Request failed',
                'data' => $e->getMessage(),
            ];
        }
    }
}
