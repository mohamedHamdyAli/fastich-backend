<?php

namespace Modules\Deliverect\App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Deliverect\App\Events\OrderSocket;
use Modules\Deliverect\App\Jobs\ProcessWebhookJob;
use Modules\Deliverect\App\Models\DeliverectOrder;

class OrderPOS
{
    protected $authService;
    protected $notificationService;
    protected $fastichProductService;

    public function __construct(
        AuthService $authService,
        NotificationService $notificationService,
        FastichProductService $fastichProductService
    ) {
        $this->authService = $authService;
        $this->notificationService = $notificationService;
        $this->fastichProductService = $fastichProductService;
    }

    function cancel_order(array $requestData,  $business_id, $pos_location)
    {
        DeliverectOrder::where('deliverect_order_id', $requestData['deliverect_order_id'])->update($requestData);
    }

    public function manage_orders(array $requestData, $business_id, $pos_location)
    {
        $isItemExists = $this->isOrderItemsExists($business_id, $pos_location, $requestData);
        // if (!$res) {
        //     $this->order_status(
        //         [
        //             'orderId' => $requestData['deliverect_order_id'],
        //             'status' => 120,
        //             'reason' => 'Product not found',
        //             'receiptId' => "",
        //         ],
        //         $business_id,
        //         $pos_location,

        //     );
        //     return [
        //         'success' => false,
        //         'message' => 'Product not found',
        //     ];
        // }
        $orderData = [
            'id' => $requestData['deliverect_order_id'],
            'posReceiptId' => $requestData['posReceiptId']
        ];
        try {
            $requestData['business_id'] = $business_id;
            $requestData['pos_location'] = $pos_location;
            $jsonFields = [
                'statusHistory',
                'packaging',
                'rating',
                'courier',
                'courierUpdateHistory',
                'customer',
                'deliveryAddress',
                'taxes',
                'payment',
                'items',
                'channelOrderHistoryRawIds',
                'discounts',
                'capacityUsages',
                'tags'
            ];
            foreach ($jsonFields as $field) {
                if (isset($requestData[$field]) && is_array($requestData[$field])) {
                    $requestData[$field] = json_encode($requestData[$field]);
                }
            }
            if (isset($requestData['pickupTime'])) {
                $requestData['pickupTime'] = $this->formatDateTime($requestData['pickupTime']);
            }

            if (isset($requestData['deliveryTime'])) {
                $requestData['deliveryTime'] = $this->formatDateTime($requestData['deliveryTime']);
            }

            if (isset($requestData['_created'])) {
                Log::info("_created Time: " . $requestData['_created']);
                $requestData['created_at'] = $this->formatDateTime($requestData['_created']);
            }

            if (isset($requestData['_updated'])) {
                Log::info("_updated Time: " . $requestData['_updated']);
                $requestData['updated_at'] = $this->formatDateTime($requestData['_updated']);
            }

            DeliverectOrder::create($requestData);
            try {
                // $res = $this->isOrderItemsExists($business_id, $pos_location, $requestData);
                Log::info("Order Items Exists: " . $isItemExists);
                if (!$isItemExists) {
                    $this->order_status(
                        [
                            'orderId' => $requestData['deliverect_order_id'],
                            'status' => 120,
                            'reason' => 'Product not found',
                            'receiptId' => "",
                        ],
                        $business_id,
                        $pos_location
                    );
                    return [
                        'success' => false,
                        'message' => 'Product not found',
                    ];
                }
            } catch (Exception $e) {
                Log::info("Exception Order Items Exists: " . $e->getMessage());

                $this->order_status(
                    [
                        'orderId' => $requestData['deliverect_order_id'],
                        'status' => 120,
                        'reason' => 'Product not found',
                        'receiptId' => "",
                    ],
                    $business_id,
                    $pos_location
                );
                return [
                    'success' => false,
                    'message' => 'Error in fetching order from db',
                ];
            }
            Log::info("Sending Message: ");

            $this->notificationService->sendNotification(
                'You missed a Deliverect order',
                $orderData,
                $business_id,
                $pos_location,
                'new-order'
            );
            return [
                'success' => true,
                'message' => 'Order inserted successfully',
            ];
        } catch (RequestException $e) {

            // TODO: Cancel the Order when Failed and log it
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            // $this->notificationService->sendNotification(
            //     'Error in order processing while inserting in the db',
            //     $orderData,
            //     $business_id,
            //     $pos_location,
            //     'error'
            // );
        }
    }

    public function order_preparation(array $data, $business_id, $pos_location)
    {
        $rules = [
            'orderID' => 'required|string|max:255',
            'orderTime' => 'required|integer',
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
                $client = new Client();
                $response = $client->request('POST', $this->authService->DeliverectURL() . '/updatePreparationTime', [
                    'json' => [
                        "order" => $validatedData['orderID'],
                        "minutes" => $validatedData['orderTime']
                    ],
                    'headers' => [
                        'authorization' => $auth->token_type . ' ' . $auth->access_token,
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                    ],
                    'verify' => false
                ]);
                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    $res = DeliverectOrder::where('deliverect_order_id', $validatedData['orderID'])->update(['preparationTime' => $validatedData['orderTime']]);
                    if ($res === 1) {
                        return ['success' => true, 'massage' => 'Order preparation time set successfully'];
                    } else {
                        return ['success' => false, 'error' => 'Unable to set order preparation time.'];
                    }
                } else {
                    return ['success' => false, 'error' => 'Unable to set order preparation time.'];
                }
            } else {
                return ['success' => false, 'error' => 'Business is not register.'];
            }
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function formatDateTime($dateTime)
    {
        if (strpos($dateTime, 'Z') !== false) {

            return \Carbon\Carbon::parse($dateTime)->setTimezone(env('APP_TIMEZONE'))->format('Y-m-d H:i:s');
        }
        return $dateTime;
    }

    public function get_deliverect_order($orderId, $business_id, $pos_location)
    {
        $auth = $this->authService->authenticate_request($business_id);
        if ($auth) {
            $order = DeliverectOrder::where('deliverect_order_id', $orderId)->first();
            $order = $order->toArray();
            Log::info("get_deliverect_order: order");
            return ['success' => true, 'order' => $order];
        } else {
            Log::info("get_deliverect_order: Business is not register");
            return ['success' => false, 'error' => 'Business is not register.'];
        }
    }


    public function order_status(array $data, $business_id, $pos_location)
    {
        $rules = [
            'orderId' => 'required|string|max:255',
            'status' => ['required'],
            'receiptId' => 'nullable',
            'reason' => 'nullable',
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            Log::info("validator errors: " .  $validator->errors());
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }
        $validatedData = $validator->validated();
        $order = DeliverectOrder::where('deliverect_order_id', $validatedData['orderId'])->first();
        if ($order && $order->status == $validatedData['status']) {
            Log::info("validatedData order_status: Order status update successfully.");
            return ['success' => true, 'message' => 'Order status update successfully'];;
        }
        try {
            $auth = $this->authService->authenticate_request($business_id);
            if ($auth) {
                $client = new Client();
                $rid = $validatedData["receiptId"] ?? "";
                $t_body = ['status' => $validatedData['status']];
                if ($rid) {
                    $t_body["posReceiptId"] = $rid;
                }
                $body = [
                    "orderId" => $validatedData['orderId'],
                    "receiptId" => $validatedData['receiptId'] ?? $order->posReceiptId,
                    'status' => $validatedData['status'],
                    'reason' => $validatedData['reason'] ?? '',
                    "timeStamp" => Carbon::now('UTC')->format('Y-m-d\TH:i:s.v\Z')
                ];
                if (isset($validatedData['reason'])) {
                    $body['reason'] = $validatedData['reason'];
                    $t_body['reason'] = $validatedData['reason'];
                }
                $response = $client->request('POST', $this->authService->DeliverectURL() . '/orderStatus/' . $validatedData['orderId'], [
                    'json' => $body,
                    'headers' => [
                        'authorization' => $auth->token_type . ' ' . $auth->access_token,
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                    ],
                    'verify' => false
                ]);
                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    $res = DeliverectOrder::where('deliverect_order_id', $validatedData['orderId'])->update($t_body);
                    Log::info('Order Status Update Response: ' . $res);
                    if ($res === 1) {
                        Log::info('statusCode:200 response 1 Order status update successfully.');
                        return ['success' => true, 'massage' => 'Order status update successfully'];
                    } else {
                        Log::info('Unable to set order status.');
                        return ['success' => false, 'error' => 'Unable to set order status.'];
                    }
                } else {
                    Log::info('Unable to set order status. else 200 statuscode.');
                    return ['success' => false, 'error' => 'Unable to set order status.'];
                }
            } else {
                Log::info('Business is not register.');
                return ['success' => false, 'error' => 'Business is not register.'];
            }
        } catch (RequestException $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function deliverectOrderTypeLabel($orderType)
    {
        $deliverectOrderType = "delivery";
        if ($orderType == 1) {
            $deliverectOrderType = "pickup";
        } else if ($orderType == 2) {
            $deliverectOrderType = "delivery";
        } else if ($orderType == 3) {
            $deliverectOrderType = "eat in";
        } else if ($orderType == 4) {
            $deliverectOrderType = "takeaway";
        }
        return $deliverectOrderType;
    }

    public function deliverectPaymentTypeLabel($orderType)
    {
        $paymentTypeLabel = 'Other';
        switch ($orderType) {
            case 0:
                $paymentTypeLabel = 'Credit Card Online';
                break;
            case 1:
                $paymentTypeLabel = 'Cash';
                break;
            case 2:
                $paymentTypeLabel = 'On Delivery';
                break;
            case 3:
                $paymentTypeLabel = 'Online';
                break;
            case 4:
                $paymentTypeLabel = 'Credit Card at Door';
                break;
            case 5:
                $paymentTypeLabel = 'PIN at Door';
                break;
            case 6:
                $paymentTypeLabel = 'Voucher at Door';
                break;
            case 7:
                $paymentTypeLabel = 'Meal Voucher';
                break;
            case 8:
                $paymentTypeLabel = 'Bank Contact';
                break;
            case 9:
                $paymentTypeLabel = 'Other';
                break;
        }
        return $paymentTypeLabel;
    }

    public function convertToLocalTime($utcTime, $timezone = "Asia/Riyadh")
    {
        $date = Carbon::parse($utcTime);
        $d = $date->setTimezone($timezone)->format('Y-m-d H:i:s');
        // Log::info("Recieved ".$utcTime);
        // Log::info("Converted ".$d);
        return $d;
    }

    public function isOrderItemsExists($business_id, $pos_location, $order)
    {
        try {
            $orderedProductsSkus = array_column($order['items'], 'plu');
            $orderedFastichProducts = $this->fastichProductService->getProducts($business_id, $pos_location, filters: [
                "product_sku" => $orderedProductsSkus
            ])['products'];
            $parsedProducts = $this->fastichProductService->parseProductsForOrderFromDeliverect(
                $order['items'],
                $orderedFastichProducts,
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function orderMapping($order, $business_id, $pos_location)
    {
        // decode the json fields
        $jsonFields = [
            'statusHistory',
            'packaging',
            'rating',
            'courier',
            'courierUpdateHistory',
            'customer',
            'deliveryAddress',
            'taxes',
            'payment',
            'items',
            'channelOrderHistoryRawIds',
            'discounts',
            'capacityUsages',
            'tags'
        ];
        foreach ($jsonFields as $field) {
            if (isset($order[$field])) {
                $order[$field] = json_decode($order[$field], true);
            }
        }

        $created_at = null;
        foreach ($order['statusHistory'] as $status) {
            if ($status['status'] == 1) {
                $created_at = $this->formatDateTime($status['timeStamp']);
                break;
            }
        }

        $orderedProductsSkus = array_column($order['items'], 'plu');
        $orderedFastichProducts = $this->fastichProductService->getProducts($business_id, $pos_location, filters: [
            "product_sku" => $orderedProductsSkus
        ])['products'];
        $parsedProducts = $this->fastichProductService->parseProductsForOrderFromDeliverect(
            $order['items'],
            $orderedFastichProducts,
        );

        $deliverectOrderType = $this->deliverectOrderTypeLabel($order['orderType']);
        $deliverectPaymentLabel = $this->deliverectPaymentTypeLabel($order['payment']['type']);

        $totalProductTax = array_sum(array_column($parsedProducts, 'total_tax'));
        $totalProductsAmount = array_sum(array_column($parsedProducts, 'total_amount'));
        $totalProductsAmountWithoutTax = array_sum(array_column($parsedProducts, 'total_amount')) - $totalProductTax;
        
        
        // $order['customer'] check if it is null then set it to empty array wiht name as empty string
        if ($order['customer'] == null) {
            $order['customer'] = ['name' => ''];
        } else if (isset($order['customer']['name']) == null) {
            $order['customer']['name'] = '';
        }

        return [
            "_id" => $order['deliverect_order_id'],
            "deliverect_order_id" => $order['deliverect_order_id'],
            "channelOrderId" => $order['channelOrderId'],
            "channelOrderDisplayId" => $order['channelOrderDisplayId'],
            "channelName" => $order['by'],
            "posId" => $order['posId'],
            "posReceiptId" => $order['posReceiptId'],
            "posLocationId" => $order['posLocationId'],
            "channelLink" => $order['channelLink'],
            "products" => $parsedProducts,
            "customer" => $order['customer'],
            "deliveryAddress" => $order['deliveryAddress'],
            "orderIsAlreadyPaid" => $order['orderIsAlreadyPaid'],
            "notes" => $order['note'] ?? "",
            "payment" => $order['payment'],
            "paymentType" => $deliverectPaymentLabel,
            "total_amount" => (float)($order['payment']['amount'] / 100),
            "status" => $order['status'],
            "date" => $order['date'],
            "deliverectOrderType" => $deliverectOrderType,
            "pickupTime" => $this->convertToLocalTime($order['pickupTime']),
            "deliveryTime" => $this->convertToLocalTime($order['deliveryTime']),
            "discountTotal" => $order['discountTotal'] / 100,
            "tip" => $order['tip'] / 100,
            "deliveryCost" => $order['deliveryCost'] / 100,
            "serviceCharge" => $order['serviceCharge'] / 100,
            "bagFee" => $order['bagFee'] / 100,
            "created_at" => $created_at,
            "totalProductsAmount" => $totalProductsAmountWithoutTax,
            "totalProductsAmountWithTax" => $totalProductsAmount,
            "totalProductTax" => $totalProductTax,
        ];
    }


    public function get_new_deliverect_order($orderId, $business_id, $pos_location)
    {
        $auth = $this->authService->authenticate_request($business_id);
        if ($auth) {
            $order = DeliverectOrder::where('deliverect_order_id', $orderId)->first();
            $order = $order->toArray();
            $orderData = $this->orderMapping($order, $business_id, $pos_location);
            return ['success' => true, 'order' => $orderData, "message" => "Order is fetched successfully"];
        } else {
            return ['success' => false, 'massage' => 'Business is not register.'];
        }
    }


    public function get_all_new_deliverect_order($business_id, $pos_location)
    {
        $auth = $this->authService->authenticate_request($business_id);
        if ($auth) {
            $orders = DeliverectOrder::whereIn('status', [10, 50, 70])
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->where('business_id', $business_id)
                ->where('pos_location', $pos_location)
                ->orderBy('created_at', 'desc')
                ->get();

            $orders = $orders->toArray();
            $ordersData = [];
            foreach ($orders as $order) {
                try {
                    $ordersData[] = $this->orderMapping($order, $business_id, $pos_location);
                } catch (Exception $th) {
                    Log::info("Error in order: " . $order['deliverect_order_id']);
                }
            }
            return ['success' => true, 'orders' => $ordersData, "message" => "New Orders are fetched successfully"];
        } else {
            return ['success' => false, 'massage' => 'Business is not register.'];
        }
    }
}
