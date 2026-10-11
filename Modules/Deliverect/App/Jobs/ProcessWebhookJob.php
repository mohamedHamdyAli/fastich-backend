<?php

namespace Modules\Deliverect\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Modules\Deliverect\App\Services\OrderPOS;
use Modules\Deliverect\App\Services\ProductPOS;
use Modules\Deliverect\App\Services\FastichProductService;
use Modules\Deliverect\App\Services\NotificationService;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $data;
    protected $business_id;
    protected $pos_location;
    protected $function;

    public function __construct(array $data, $business_id, $pos_location, $function)
    {
        $this->data = $data;   //
        $this->business_id = $business_id;
        $this->pos_location = $pos_location;
        $this->function = $function;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductPOS $productService, OrderPOS $orderService, FastichProductService $fastichProductService, NotificationService $notificationService)
    {
        Log::info('function handle: ' . $this->function);
        if ($this->function === 'sync-products') {
            Log::info('sync products run');
            $fastichProductService->syncProducts($this->business_id, $this->pos_location);
        } else if ($this->function === 'order-status') {
            Log::info('order status run');
            $orderService->order_status($this->data, $this->business_id, $this->pos_location);
        } else if ($this->function === 'order-notification') {
            $result = $orderService->manage_orders($this->data, $this->business_id, $this->pos_location);
            if (!$result['success']) {
                Log::info('manage_orders: order status success.');
                return;
            }
            $msg = 'Sleeping for 10 sec to check if the pos responsd that it recived the order, Order ID: ';
            $msg .= $this->data['deliverect_order_id'];
            $msg .= ', BussinessId: ' . $this->business_id;
            $msg .= ', PosLocation: ' . $this->pos_location;
            Log::info($msg . $this->data['deliverect_order_id']);

            // add a timer of 10 sec to check if the order is received by POS or not
            // after 10 sec get the order status from the db if it is 1 then it means pos is off
            // call the cancel order api
            sleep(10);
            Log::info('Checking order status after 10 sec, Order ID: ' . $this->data['deliverect_order_id']);
            $response = $orderService->get_deliverect_order($this->data['deliverect_order_id'], $this->business_id, $this->pos_location);
            if (!$response['success']) {
                Log::info('Error in getting order from db after 10 sec');
            } else {
                $order = $response['order'];
                if ($order["status"] == "1") {
                    $data = [
                        'orderId' => $order['deliverect_order_id'],
                        'status' => 110,
                        'reason' => 'POS is not active',
                        'receiptId' => $order['deliverect_order_id'] ?? "",
                    ];
                    Log::info('Order Status Data: ', $data);
                    $res = $orderService->order_status($data, $this->business_id, $this->pos_location);
                    Log::info('Order is not received by POS, Order ID: ' . $this->data['deliverect_order_id'], $res);
                }
            }
        } else if ($this->function === 'cancel-order-notification') {
            $data = [
                'orderId' => $this->data['deliverect_order_id'],
                'status' => 110,
                'receiptId' => $this->data['posReceiptId'],
            ];
            // $orderService->cancel_order($data);
            $orderService->order_status($data, $this->business_id, $this->pos_location);
            $notificationService->sendNotification(
                'Order is cancelled',
                [
                    'id' => $this->data['deliverect_order_id'],
                    'posReceiptId' => $this->data['posReceiptId']
                ],
                $this->business_id,
                $this->pos_location,
                'cancel-order'
            );
        }
    }
}
