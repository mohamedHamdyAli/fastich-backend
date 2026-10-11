<?php


namespace Modules\Deliverect\App\Services;
use Modules\Deliverect\App\Models\DeliverectChannel;
use Illuminate\Support\Facades\Log;

class DeliverectChannelService
{
    public function addOrUpdateChannels($businessId, array $channels)
    {
        try {
            foreach ($channels as $channel) {
                DeliverectChannel::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'channel_id' => $channel['channelId']
                    ],
                    [
                        'channel_name' => $channel['name']
                    ]
                );
            }

            return [
                'success' => true,
                'data' => $channels,
                'message' => 'Channels synced successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Channel sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ];
        }
    }

    public function getBusinessChannels($businessId)
    {
        $channels = DeliverectChannel::where('business_id', $businessId)->get();
        
        return [
            'success' => true,
            'data' => $channels ? $channels->toArray() : [],
            'message' => 'Channels retrieved successfully'
        ];
    }

    public function deleteChannels($businessId, array $channelIds = [])
    {
        try {
            $query = DeliverectChannel::where('business_id', $businessId);
            
            if (!empty($channelIds)) {
                $query->whereNotIn('channel_id', $channelIds);
            }

            $deletedCount = $query->delete();

            return [
                'success' => true,
                'data' => ['deleted_count' => $deletedCount],
                'message' => 'Channels deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ];
        }
    }
}