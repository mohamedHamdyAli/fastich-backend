<?php

namespace Modules\Deliverect\App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Modules\Deliverect\App\Models\DeliverectAuthentication;
use Illuminate\Support\Facades\Validator;

class AuthService
{

    public function DeliverectURL()
    {
        if (app()->environment('production')) {
            return env('DELIVERECT_PRODUCTION');
        } else {
            return env('DELIVERECT_STAGING');
        }
    }

    public function onBoardWithDeliverectStatus($business_id)
    {
        $result = DeliverectAuthentication::where('business_id', $business_id)->first();
        if ($result) {
            return ['success' => true, 'data' => $result];
        } else {
            return ['success' => false, 'message' => 'Business is not authenticate'];
        }
    }

    public function onBoardWithDeliverect(array $data, $business_id)
    {
        $rules = [
            'client_secret' => 'required|string|max:255',
            'client_id' => 'required|string|max:255',
            'audience' => 'nullable|url'
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()
            ];
        }

        $validatedData = $validator->validated();
        $clientSecret = $validatedData['client_secret'];
        $clientId = $validatedData['client_id'];
        $audience = $validatedData['audience'] ??  $this->DeliverectURL();
        $grantType = 'token';

        try {
            $client = new Client();
            $response = $client->request('POST', $this->DeliverectURL() . '/oauth/token', [
                'json' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'audience' => $audience,
                    'grant_type' => $grantType,
                ],
                'verify' => false
            ]);

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                DeliverectAuthentication::updateOrCreate(
                    ['business_id' => $business_id],
                    [

                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'token_type' => $data['token_type'],
                        'access_token' => $data['access_token'],
                        'expires_at' => $data['expires_at']
                    ]
                );

                $APP_URL = env('BASE_URL_FOR_DELIVERECT');

                return [
                    'success' => true,
                    'message' => 'Deliverect authenticate successful',
                    "data" => []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Request failed',
                    'data' => $data
                ];
            }
        } catch (RequestException $e) {
            \Log::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Request failed ' . $e->getMessage(),
                'data' => []
            ];
        }
    }


    // ABOVE ARE VERIFIED

    public function authenticate_request($business_id)
    {
        try {
            $auth = DeliverectAuthentication::where('business_id', $business_id)->first();
            if ($auth) {
                $timestamp = time();
                if ($timestamp < $auth->expires_at) {
                    return $auth;
                } else {
                    $client = new Client();
                    $response = $client->request('POST', $this->DeliverectURL() . '/oauth/token', [
                        'json' => [
                            'client_id' => $auth->client_id,
                            'client_secret' => $auth->client_secret,
                            'audience' => $this->DeliverectURL(),
                            'grant_type' => 'token'
                        ],
                        'verify' => false
                    ]);
                    $statusCode = $response->getStatusCode();
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($statusCode === 200) {
                        $new_auth = DeliverectAuthentication::where('business_id', $business_id)->update([
                            'token_type' => $data['token_type'],
                            'access_token' => $data['access_token'],
                            'expires_at' => $data['expires_at']
                        ]);
                        if ($new_auth === 1) {
                            $updated_auth = DeliverectAuthentication::where('business_id', $business_id)->first();
                            return $updated_auth;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } catch (RequestException $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
