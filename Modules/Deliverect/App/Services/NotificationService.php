<?php 

namespace Modules\Deliverect\App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{

    public function __construct()
    {
        
    }

    public function sendNotification($content, $data, $business_id, $pos_location, $type)
    {   
        // OneSignal API configuration
        $appId = env('ONESIGNAL_APP_ID');
        $restApiKey = env('ONESIGNAL_REST_API_KEY');

        // Guzzle HTTP client
        $client = new Client([
            'base_uri' => 'https://onesignal.com/api/v1/',
            'headers' => [
                'Authorization' => 'Basic ' . $restApiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
        
        // Notification data
        $notificationData = [
            'app_id' => $appId,
            'filters' => [
                ['field' => 'tag', 'key' => 'business_id', 'relation' => '==', 'value' => $business_id],
                ['field' => 'tag', 'key' => 'location_id', 'relation' => '==', 'value' => $pos_location],
            ],
            "small_icon" => "ic_launcher",
            'contents' => [
                'en' => $content, // Notification content
            ],
            'data' => [
                'business_id' => $business_id,
                'location_id' => $pos_location,
                'type' => $type,
                'timestamp' => Carbon::now('UTC')->timestamp,
                'data' => $data, // Additional data
            ],
        ];

        Log::info("Notification Payload", $notificationData);
    
        try {
            // Send the notification
            $response = $client->post('notifications', [
                'json' => $notificationData,
            ]);
            
            $jsonResponse = json_decode($response->getBody()->getContents(), true);
            // Log the response
            Log::info('Notificaiton Payload: ', $notificationData);
            Log::info('Notification sent: ', $jsonResponse);
    
            return $response;
        } catch (RequestException $e) {
            // Log the error
            Log::error('Error sending notification: ' . $e->getMessage());
            if ($e->hasResponse()) {
                $jsonResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
                Log::error('Response: ', $jsonResponse);
            }
    
            return null;
        }
    }
}