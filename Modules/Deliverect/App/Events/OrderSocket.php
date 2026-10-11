<?php

namespace Modules\Deliverect\App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;

class OrderSocket
{
    use SerializesModels;

    public $order;
    public $business_id;
    public $pos_location;

    public function __construct($order, $business_id, $pos_location)
    {
        $this->order = $order;
        $this->business_id = $business_id;
        $this->pos_location = $pos_location;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('order_notification_' . $this->business_id . '_' . $this->pos_location);
    }
    public function broadcastWith()
    {
        return [
            'business_id' => $this->business_id, 
            'pos_location' => $this->pos_location, 
            'order' => $this->order
        ];
    }
}
