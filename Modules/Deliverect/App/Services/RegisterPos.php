<?php

namespace Modules\Deliverect\App\Services;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Modules\Deliverect\App\Models\DeliverectRegistration;

class RegisterPos
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function get_registration_url($business_id, $pos_location)
    {
        $auth = $this->authService->authenticate_request($business_id);
        if ($auth) {
            $APP_URL = env('BASE_URL_FOR_DELIVERECT');
            return [
                'success' => true,
                'data' => [
                    'registrationURL' => $APP_URL . '/api/deliverect/webhooks/register-pos'
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Business not found'
            ];
        }
    }

    public function createMaopo($business_id, $pos_location)
    {
        $auth = $this->authService->authenticate_request($business_id);
        if ($auth) {
            $APP_URL = env('BASE_URL_FOR_DELIVERECT');
            return [
                'success' => true,
                'data' => [
                    'registrationURL' => $APP_URL . '/api/deliverect/webhooks/register-pos'
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Business not found'
            ];
        }
    }


    public function get_registration_status($business_id, $pos_location)
    {
        try {
            $result = DeliverectRegistration::where('business_id', $business_id)->where('pos_location', $pos_location)->first();
            return [
                'success' => true,
                'data' => [
                    'status' => $result ? true : false
                ],
                "message" => $result ? 'Location is registered' : 'Location is not registered'
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ];
        }
    }

    public function get_registration_info($business_id, $pos_location)
    {
        try {
            $result = DeliverectRegistration::where('business_id', $business_id)->where('pos_location', $pos_location)->first();
            return [
                'success' => $result ? true : false,
                'data' => $result ? [
                    "deliverect_loc_id" => $result['location_id'],
                ] : null,
                "message" => $result ? 'Location is registered' : 'Location is not registered'
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ];
        }
    }

    public function registration_pos(array $data, $business_id, $pos_location)
    {
        $rules = [
            'accountId' => 'required|string|max:255',
            'locationId' => 'required|string|max:255',
            'externalLocationId' => 'required|string|max:255',
            'locationName' => 'required|string',
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()
            ];
        }

        $validatedData = $validator->validated();
        try {
            $auth = $this->authService->authenticate_request($business_id);
            if ($auth) {
                $reg = DeliverectRegistration::updateOrCreate(
                    ['pos_location' => $pos_location],
                    [
                        'business_id' => $business_id,
                        'pos_location' => $pos_location,
                        'account_id' => $validatedData['accountId'],
                        'location_id' => $validatedData['locationId'],
                        'external_Location_id' => $validatedData['externalLocationId'],
                        'location_name' => $validatedData['locationName']
                    ]
                );

                if ($reg) {
                    $APP_URL = env('BASE_URL_FOR_DELIVERECT');
                    return [
                        'success' => true,
                        'data' => [
                            'ordersWebhookURL' => $APP_URL . '/api/deliverect/' . $business_id . '/' . $pos_location .  '/webhooks/order-notification',
                            'syncProductsURL' => $APP_URL . '/api/deliverect/' . $business_id . '/' . $pos_location .  '/webhooks/sync-products',
                            'syncTablesURL' => $APP_URL . '/api/deliverect/' . $business_id . '/' . $pos_location .  '/webhooks/tables'
                        ]
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to register location'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Business not found'
                ];
            }
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ];
        }
    }
}
