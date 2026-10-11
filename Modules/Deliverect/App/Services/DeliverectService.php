<?php

namespace Modules\Deliverect\App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Validator;
use Modules\Deliverect\App\Models\DeliverectRegistration;

class DeliverectService
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function get_brands($business_id, $pos_location)
    {
        try {
            $auth = $this->authService->authenticate_request($business_id);
            if ($auth) {
                $registration = DeliverectRegistration::where('business_id', $business_id)->where('pos_location', $pos_location)->first();
                if ($registration) {
                    $client = new Client();
                    $response = $client->request('GET', $this->authService->DeliverectURL() . '/accounts/' . $registration['account_id'] . '/brands', [
                        'headers' => [
                            'accept' => 'application/json',
                            'authorization' => $auth->token_type . ' ' . $auth->access_token,
                        ],
                        'verify' => false
                    ]);
                    $statusCode = $response->getStatusCode();
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($statusCode === 200) {
                        return [
                            'success' => true,
                            'data' => $data
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => $data
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Business is not register'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Unauthorize Business'
                ];
            }
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function get_allergens_tags($business_id, $pos_location)
    {
        try {
            $auth = $this->authService->authenticate_request($business_id);
            if ($auth) {
                $registration = DeliverectRegistration::where('business_id', $business_id)->where('pos_location', $pos_location)->first();
                if ($registration) {
                    $client = new Client();
                    $response = $client->request('GET', $this->authService->DeliverectURL() . '/allAllergens', [
                        'headers' => [
                            'accept' => 'application/json',
                            'authorization' => $auth->token_type . ' ' . $auth->access_token,
                        ],
                        'verify' => false
                    ]);
                    $statusCode = $response->getStatusCode();
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($statusCode === 200) {
                        return [
                            'success' => true,
                            'data' => $data
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => $data
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Business is not register'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Unauthorize Business'
                ];
            }
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function get_channels($business_id)
    {
        try {
            $auth = $this->authService->authenticate_request($business_id);
            if ($auth) {
                $client = new Client();
                $response = $client->request('GET', $this->authService->DeliverectURL() . '/allChannels', [
                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => $auth->token_type . ' ' . $auth->access_token,
                    ],
                    'verify' => false
                ]);
                $statusCode = $response->getStatusCode();
                $data = json_decode($response->getBody()->getContents(), true);
                $is_success = $statusCode === 200;
                return [
                    'success' => $is_success,
                    'data' => $data,
                    'message' => $is_success ? "Channels fetched successfully" : "Failed to fetch channels"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unauthorize Business',
                    "data" => []
                ];
            }
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function get_categories($business_id, $pos_location)
    {
        try {
            $auth = $this->authService->authenticate_request($business_id);
            if ($auth) {
                $registration = DeliverectRegistration::where('business_id', $business_id)->where('pos_location', $pos_location)->first();
                if ($registration) {
                    $client = new Client();
                    $response = $client->request('GET', $this->authService->DeliverectURL() . '/productCategories?where={"account": "' . $registration['account_id'] . '"}', [
                        'headers' => [
                            'accept' => 'application/json',
                            'authorization' => $auth->token_type . ' ' . $auth->access_token,
                        ],
                        'verify' => false
                    ]);
                    $statusCode = $response->getStatusCode();
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($statusCode === 200) {
                        return [
                            'success' => true,
                            'data' => $data
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => $data
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Business is not register'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Unauthorize Business'
                ];
            }
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
