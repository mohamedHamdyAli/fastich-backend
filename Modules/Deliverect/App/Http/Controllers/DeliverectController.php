<?php
namespace Modules\Deliverect\App\Http\Controllers;

// Laravel Imports
use App\Business;
use App\BusinessLocation;
use App\CallNumberScheme;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Media;

// Custom Packages Import

// Fasitch Imports
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// Deliverect Imports
use Modules\Deliverect\App\Jobs\ProcessWebhookJob;
use Modules\Deliverect\App\Services\AuthService;
use Modules\Deliverect\App\Services\DeliverectChannelService;
use Modules\Deliverect\App\Services\DeliverectService;
use Modules\Deliverect\App\Services\FastichProductService;
use Modules\Deliverect\App\Services\LocationMappingService;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Deliverect\App\Services\NotificationService;
use Modules\Deliverect\App\Services\OrderPOS;
use Modules\Deliverect\App\Services\ProductPOS;
use Modules\Deliverect\App\Services\RegisterPos;

class DeliverectController extends Controller
{
    protected $deliverectService;
    protected $authService;
    protected $productPOSService;
    protected $fastichProductService;
    protected $notificationService;
    protected $orderService;
    protected $registrationService;
    protected $productUtil;
    protected $contactUtil;
    protected $commonUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $cashRegisterUtil;
    protected $locationMappingService;
    protected $channelService;

    public function __construct(
        DeliverectService $deliverectService,
        AuthService $authService,
        ProductPOS $productPOSService,
        FastichProductService $fastichProductService,
        NotificationService $notificationService,
        OrderPOS $orderService,
        RegisterPos $registrationService,
        ProductUtil $productUtil,
        ContactUtil $contactUtil,
        BusinessUtil $businessUtil,
        Util $commonUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil,
        LocationMappingService $locationMappingService,
        DeliverectChannelService $channelService,

    ) {
        $this->deliverectService      = $deliverectService;
        $this->authService            = $authService;
        $this->productPOSService      = $productPOSService;
        $this->fastichProductService  = $fastichProductService;
        $this->notificationService    = $notificationService;
        $this->orderService           = $orderService;
        $this->registrationService    = $registrationService;
        $this->productUtil            = $productUtil;
        $this->contactUtil            = $contactUtil;
        $this->businessUtil           = $businessUtil;
        $this->commonUtil             = $commonUtil;
        $this->transactionUtil        = $transactionUtil;
        $this->cashRegisterUtil       = $cashRegisterUtil;
        $this->moduleUtil             = $moduleUtil;
        $this->notificationUtil       = $notificationUtil;
        $this->locationMappingService = $locationMappingService;
        $this->channelService         = $channelService;
        $this->dummyPaymentLine       = [
            'method'                  => 'cash',
            'amount'                  => 0,
            'note'                    => '',
            'card_transaction_number' => '',
            'card_number'             => '',
            'card_type'               => '',
            'card_holder_name'        => '',
            'card_month'              => '',
            'card_year'               => '',
            'card_security'           => '',
            'cheque_number'           => '',
            'bank_account_number'     => '',
            'is_return'               => 0,
            'transaction_no'          => '',
        ];
    }

    public function onBoardWithDeliverect(Request $request, $business_id)
    {
        $result = $this->authService->onBoardWithDeliverect($request->all(), $business_id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 422);
        }
    }

    public function onBoardWithDeliverectStatus(Request $request, $business_id)
    {
        $result = $this->authService->onBoardWithDeliverectStatus($business_id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 422);
        }
    }

    public function getRegistrationUrl(Request $request, $business_id, $pos_location)
    {
        $result = $this->registrationService->get_registration_url($business_id, $pos_location);
        if ($result['success']) {
            return response()->json($result['data'], 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function mapDeliverectLocation(Request $request, $business_id, $pos_location)
    {
        $request->validate([

            'deliverect_loc_id' => 'required|string',
        ]);

        $deliverectLocId = $request->input('deliverect_loc_id');

        $result = $this->locationMappingService->addMapping($business_id, $pos_location, $deliverectLocId);

        return response()->json($result);
    }

    public function registerationStatus(Request $request, $business_id, $pos_location)
    {
        $result = $this->registrationService->get_registration_status($business_id, $pos_location);
        return response()->json($result, 200);
    }

    // function registerPos(Request $request)
    // {
    //     $result = $this->locationMappingService->getByDeliverectLocationId($request->input("locationId"));
    //     if ($result['success']) {
    //         $business_id = $result['data']['business_id'];
    //         $pos_location = $result['data']['pos_location'];
    //         $result = $this->registrationService->registration_pos($request->all(), $business_id, $pos_location);
    //         if ($result['success']) {
    //             return response()->json($result['data'], 200);
    //         } else {
    //             return response()->json($result, 400);
    //         }
    //     } else {
    //         return response()->json([
    //             "message" => "Threre is no mapping exist for this deliverect location",
    //             "success" => false,
    //         ], 400);
    //     }
    // }

    public function getDeliverectLocation(Request $request, $business_id, $pos_location)
    {
        $result = $this->registrationService->get_registration_info($business_id, $pos_location);
        return response()->json($result, 200);
    }

    public function registerPos(Request $request)
    {
        $externalLocationId = $request->input("externalLocationId");
        $businessLocation   = BusinessLocation::where('location_id', $externalLocationId)->first();

        if ($businessLocation) {
            $business_id = $businessLocation->business_id;
            $location_id = $businessLocation->id;

            $result = $this->registrationService->registration_pos($request->all(), $business_id, $location_id);
            if ($result['success']) {
                return response()->json($result['data'], 200);
            } else {
                return response()->json($result, 400);
            }
        } else {
            return response()->json([
                "message" => "Threre is no mapping exist for this deliverect location",
                "success" => false,
            ], 400);
        }
    }

    // ABOVE FUNCTIONS ARE VERIFIED

    public function syncProducts(Request $request, $business_id, $pos_location)
    {
        ProcessWebhookJob::dispatch($request->all(), $business_id, $pos_location, 'sync-products');
        return response()->json([], 204);
    }

    public function updateOrderPreparation(Request $request, $business_id, $pos_location)
    {
        $result = $this->orderService->order_preparation($request->all(), $business_id, $pos_location);
        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function updateOrderStatus(Request $request, $business_id, $pos_location)
    {
        $result = $this->orderService->order_status($request->all(), $business_id, $pos_location);
        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function recivedByPosOrder(Request $request, $business_id, $pos_location)
    {
        $orderData = $request->all()['orderData'];
        $response  = $this->orderService->order_status([
            'orderId' => $orderData['id'],
            'status'  => 10,
        ], $business_id, $pos_location);

        // if ($response['success']) {

        //     return response()->json($response, 400);
        // } else {
        //     return response()->json($response, 400);
        // }

        $response = $this->orderService->get_new_deliverect_order($orderData['id'], $business_id, $pos_location);
        if ($response['success']) {
            $order = $response['order'];
            return response()->json($response, 200);
        }

        return response()->json([
            "message" => "Order status updated to new",
            "success" => true,
        ], 200);
    }

    public function getDeliverectOrderDetail(Request $request, $business_id, $pos_location)
    {
        $order_id = $request->input('order_ids');
        $orders   = [];
        for ($i = 0; $i < count($order_id); $i++) {

            try {
                $orderId = $order_id[$i];
                $result  = $this->orderService->get_deliverect_order($orderId, $business_id, $pos_location);
                if ($result['success']) {
                    $orders[$orderId] = $this->orderService->orderMapping($result['order'], $business_id, $pos_location);
                } else {
                    Log::info("Order not found: " . $orderId);
                    $orders[$orderId] = null;
                }
            } catch (\Exception $e) {
                Log::info("Error occured while getting the order for order id " . $orderId . ": " . $e->getMessage());
                $orders[$orderId] = null;
            }
        }
        return response()->json([
            "success" => true,
            "orders"  => $orders,
        ], 200);
    }

    public function getAllNewOrders(Request $request, $business_id, $pos_location)
    {
        Log::info("getAllNewOrders: function run.");
        $userId = $request->input('user_id');
        $result = $this->orderService->get_all_new_deliverect_order($business_id, $pos_location);
        if ($result['success']) {
            $orders = $result['orders'];
            for ($i = 0; $i < count($orders); $i++) {
                // order status in [50, 70]
                if (in_array($orders[$i]['status'], ["50", "70"])) {
                    $res = $this->getSuspendedSells(
                        $userId,
                        $business_id,
                        $pos_location,
                        $orders[$i]['deliverect_order_id']
                    );
                    // $orders[$i]['fastich_order'] = $res;
                    if ($res['success'] == 1) {
                        $orders[$i]['fastich_order'] = $res['order'];
                    } else {
                        $orders[$i]['fastich_order'] = null;
                    }
                }
            }
            return response()->json([
                "success" => true,
                "orders"  => $orders,
            ], 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function orderNotification(Request $request, $business_id, $pos_location)
    {
        // $res = Artisan::call('queue:work', [
        //     '--stop-when-empty' => true, // Stop the worker after processing jobs
        //     '--queue' => 'default',     // Specify the queue
        // ]);

        Log::info("Staring the order notification");

        $status = $request->input('status');

        // 1 -> 10 (Accept, Reject) -> (50, 120) if 50(Prepare) -> (Pickup Ready, Reject)(70, 120)
        $statusMappings = [
            1   => 'Parsed',       // deliverect status
            10  => 'New',          // Accept, Reject
            20  => 'Accept',       // Accept, Reject
            50  => "Preparing",    // Prepared, Reject
            70  => 'Pickup Ready', // Finalized
            90  => 'Finalized',
            100 => 'Cancel',    // cancelled by the channel
            110 => 'Cancelled', // cancelled by the POS (Could be due to some reason)
            120 => 'Failed',    // Order has not been successful for any number of reasons
        ];

        $data = $request->all();

        $data['deliverect_order_id'] = $data['_id'];
        $data['posReceiptId']        = "";
        // remove the _id
        unset($data['_id']);

        if ($status == 1) {
            ProcessWebhookJob::dispatch($data, $business_id, $pos_location, 'order-notification');
            return response()->json([], 200);
        } else if ($status == 100) {
            ProcessWebhookJob::dispatch($data, $business_id, $pos_location, 'cancel-order-notification');
            return response()->json([], 110);
            $this->orderService->cancel_order($data);
        }
    }

    public function getChannels(Request $request, $business_id, $pos_location)
    {
        $result = $this->deliverectService->get_channels($business_id);
        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function syncChannels(Request $request, $business_id)
    {
        $result = $this->deliverectService->get_channels($business_id);
        if ($result['success']) {
            $res = $this->channelService->addOrUpdateChannels(
                $business_id,
                $result['data']
            );
            return response()->json($res, $res['success'] ? 200 : 400);
        } else {
            return response()->json($result, 400);
        }
    }

    public function getAllergensAndTags(Request $request, $business_id, $pos_location)
    {
        $result = $this->deliverectService->get_allergens_tags($business_id, $pos_location);
        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function getBrands(Request $request, $business_id, $pos_location)
    {
        $result = $this->deliverectService->get_brands($business_id, $pos_location);
        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function getCategories(Request $request, $business_id, $pos_location)
    {
        $locationID = $request->query('location_id') ?? false;
        $result     = $this->deliverectService->get_categories($business_id, $pos_location);
        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 400);
        }
    }

    public function internalSyncProducts(Request $request, $business_id, $pos_location)
    {
        $selectedProductsIds = $request->input('selected_rows');
        $selectedProductsIds = array_map('intval', $selectedProductsIds);
        return $this->fastichProductService->syncProducts($business_id, $pos_location, $selectedProductsIds);
    }

    public function getApiProducts(Request $request, $business_id, $pos_location)
    {
        $filters  = $request->all();
        $products = $this->fastichProductService->getProducts($business_id, $pos_location, $filters);
        return response()->json($products, $products['status']);
    }

    public function checkOneSignalSubscription(Request $request, $business_id, $pos_location)
    {
        $otp = $request->input('otp');
        $this->notificationService->sendNotification(
            "Your OTP code is: " . $otp,
            [
                "otp" => $otp,
            ],
            $business_id,
            $pos_location,
            "otp"
        );
        $otp = $request->input('otp');

        return response()->json([
            "message" => "Notification sent to user",
            "success" => true,
        ]);
    }

    public function onAcceptNewOrder(Request $request, $business_id, $pos_location)
    {
        $prepration_time     = $request->input('prepration_time');
        $deliverect_order_id = $request->input('deliverect_order_id');
        $order               = $this->orderService->get_deliverect_order($deliverect_order_id, $business_id, $pos_location);
        try {
            if ($order['success']) {
                $order    = $order['order'];
                $response = $this->orderService->order_status([
                    'orderId' => $deliverect_order_id,
                    'status'  => 20, // Accepted
                ], $business_id, $pos_location);
                if (! $response['success']) {
                    return response()->json($response, 400);
                }
            } else {
                return response()->json($order, 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error occured while accepting the order",
                "success" => false,
            ], 400);
        }

        //  update the prepration time

        // try {
        //     $response = $this->orderService->order_preparation([
        //         'orderID' => $deliverect_order_id,
        //         'orderTime' => $prepration_time,
        //     ], $business_id, $pos_location);
        //     Log::info("Prep Response: ", $response);
        //     if (!$response['success']) {
        //         return response()->json($response, 400);
        //     }
        // } catch (\Exception $e) {
        //     Log::info("Error occured while update the prepration time: " . $e->getMessage());
        //     return response()->json([
        //         "message" => "Error occured while update the prepration time",
        //         "success" => false,
        //     ], 400);
        // }

        Log::info("Staring the Transaction Insertion: ");
        try {

            $input                  = $request->all();
            $deliverectOrderDetails = $input['deliverect_order_details'];
            $input["tip"]           = $deliverectOrderDetails['tip'];
            $input["deliveryCost"]  = $deliverectOrderDetails['deliveryCost'];
            $input["serviceCharge"] = $deliverectOrderDetails['serviceCharge'];
            $input["bagFee"]        = $deliverectOrderDetails['bagFee'];
            //return response()->json(['success' => 1, 'input' => $input], 200);
            $user_id     = $input['user_id'] ?? "";
            $business_id = $business_id;
            $user        = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.add')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized  action! Not alowed add POS sell.'], 403);
                }
                $selectedBusiness = Business::where('id', $business_id)->first();
                $enabled_modules  = $this->commonUtil->allModulesEnabled($business_id);

                //$enabled_modules = $selectedBusiness->enabled_modules ?? "";
                $is_direct_sale = false;
                if (! empty($request->input('is_direct_sale'))) {
                    $is_direct_sale = true;
                }
                //Check if there is a open register, if no then redirect to Create Register screen.
                if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister($user_id) == 0) {
                    return response()->json(['success' => 0, 'msg' => __('cash_register.register_not_opened')], 500);
                }

                /*  $input = $request->except('_token') ?? ""; */

                // Contact Id
                $contact_id = $request->get('contact_id', null);

                if ($contact_id == 'no_customer_select') {
                    $contact_id = Contact::where('business_id', $business_id)->first()->id;
                }
                $input['contact_id']   = $contact_id;
                $input['is_quotation'] = 0;
                /* status is send as quotation from Add sales screen. */
                if ($input['status'] == 'quotation') {
                    $input['status']       = 'draft';
                    $input['is_quotation'] = 1;
                }

                if (! empty($input['products'])) {
                    $discount = [
                        'discount_type'   => $input['discount_type'],
                        'discount_amount' => $input['discount_amount'],
                    ];
                    $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                    DB::beginTransaction();

                    if (empty($request->input('transaction_date'))) {
                        $input['transaction_date'] = Carbon::now();
                    } else {
                        $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                    }
                    if ($is_direct_sale) {
                        $input['is_direct_sale'] = 1;
                    }

                    //Set commission agent
                    $input['commission_agent'] = ! empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                    $commsn_agnt_setting       = $request->get('sales_cmsn_agnt');
                    if ($commsn_agnt_setting == 'logged_in_user') {
                        $input['commission_agent'] = $user_id;
                    }

                    if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                        $input['exchange_rate'] = 1;
                    }

                    //Customer group details
                    $cg                         = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                    $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                    //set selling price group id
                    $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                    //If default price group for the location exists
                    $price_group_id = $price_group_id == 0 && $request->has('default_price_group') ? $request->input('default_price_group') : $price_group_id;

                    $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                    if ($input['is_suspend']) {
                        $input['sale_note'] = ! empty($input['additional_notes']) ? $input['additional_notes'] : null;
                    }

                    //Generate reference number
                    if (! empty($input['is_recurring'])) {
                        //Update reference count
                        $ref_count                = $this->transactionUtil->setAndGetReferenceCount('subscription');
                        $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                    }

                    if (! empty($request->input('invoice_scheme_id'))) {
                        $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                    }

                    //Types of service
                    if ($this->commonUtil->isModuleEnabled('direct_sales_module', $business_id)) {
                        $input['types_of_service_id'] = $request->input('types_of_service_id');
                        $price_group_id               = ! empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                        $input['packing_charge']      = ! empty($request->input('packing_charge')) ?
                        $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                        $input['packing_charge_type']    = $request->input('packing_charge_type');
                        $input['service_custom_field_1'] = ! empty($request->input('service_custom_field_1')) ?
                        $request->input('service_custom_field_1') : null;
                        $input['service_custom_field_2'] = ! empty($request->input('service_custom_field_2')) ?
                        $request->input('service_custom_field_2') : null;
                        $input['service_custom_field_3'] = ! empty($request->input('service_custom_field_3')) ?
                        $request->input('service_custom_field_3') : null;
                        $input['service_custom_field_4'] = ! empty($request->input('service_custom_field_4')) ?
                        $request->input('service_custom_field_4') : null;
                    }

                    $input['selling_price_group_id'] = $price_group_id;

                    if ($this->commonUtil->isModuleEnabled('table_module', $business_id)) {
                        $input['res_table_id'] = request()->get('res_table_id');
                    }
                    if ($this->commonUtil->isModuleEnabled('service_staff_module', $business_id)) {
                        $input['res_waiter_id'] = request()->get('res_waiter_id');
                    }
                    $input['order_type_Id']       = request()->get('order_type_Id') ?? "";
                    $input['is_created_from_api'] = 1;

                    $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
                    Log::info("Transaction: Created ");
                    $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], false, null, [], true, $enabled_modules, $business_id);

                    $call_no = CallNumberScheme::callNumberGeneration($business_id, $input['location_id'], $transaction->id, $user_id);

                    if (! $is_direct_sale) {
                        //Add change return
                        $change_return              = $this->dummyPaymentLine;
                        $change_return['amount']    = $input['change_return'];
                        $change_return['is_return'] = 1;
                        $input['payment'][]         = $change_return;
                    }

                    $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                    if (! $transaction->is_suspend && ! empty($input['payment']) && ! $is_credit_sale) {
                        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment'], $business_id, $user_id);
                    }

                    //Check for final and do some processing.
                    if (isset($input['status']) && ($input['status'] == 'final')) {
                        foreach ($input['products'] as $product) {
                            $decrease_qty = $this->productUtil
                                ->num_uf($product['quantity']);
                            if (! empty($product['base_unit_multiplier'])) {
                                $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                            }
                            if ($product['enable_stock']) {
                                $this->productUtil->decreaseProductQuantity($product['product_id'], $product['variation_id'], $input['location_id'], $decrease_qty);
                            } else {
                                $recipe = MfgRecipe::where('variation_id', $product['variation_id'])
                                    ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit',
                                        'ingredients' => function ($query) {
                                            $query->orderBy('sort_order', 'asc');
                                        }])
                                    ->first();

                                if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($product)) {
                                    $this->getDecreaseInventory($recipe, $business_id, $location_id, $product);
                                }
                                if (! empty($product['modifier'])) {
                                    foreach ($product['modifier'] as $key => $modifier_id) {
                                        $recipeModifier = MfgRecipe::where('variation_id', $modifier_id)
                                            ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit',
                                                'ingredients' => function ($query) {
                                                    $query->orderBy('sort_order', 'asc');
                                                }])
                                            ->first();
                                        if (! empty($recipeModifier) && ! empty($business_id) && ! empty($location_id) && ! empty($product)) {
                                            $this->getDecreaseInventory($recipeModifier, $business_id, $location_id, $product);
                                        }
                                    }
                                }
                            }

                            if ($product['product_type'] == 'combo') {
                                //Decrease quantity of combo as well.
                                $this->productUtil
                                    ->decreaseProductQuantityCombo(
                                        $product['combo'],
                                        $input['location_id']
                                    );
                            }
                        }

                        //Add payments to Cash Register
                        if (! $is_direct_sale && ! $transaction->is_suspend && ! empty($input['payment']) && ! $is_credit_sale) {
                            $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'], $input['clientCompany'], $user_id);
                        }

                        //Update payment status
                        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                        if ($request->get('enable_rp') == 1) {
                            $redeemed = ! empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                            $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                        }

                        //Allocate the quantity from purchase and add mapping of
                        //purchase & sell lines in
                        //transaction_sell_lines_purchase_lines table
                        $business_details = $this->businessUtil->getDetails($business_id);
                        $pos_settings     = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                        $business = [
                            'id'                => $business_id,
                            'accounting_method' => $request->get('accounting_method'),
                            'location_id'       => $input['location_id'],
                            'pos_settings'      => $pos_settings,
                        ];
                        $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                        //Auto send notification
                        $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                    }

                    //Set Module fields
                    if (! empty($input['has_module_data'])) {
                        $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                    }

                    Media::uploadMedia($business_id, $transaction, $request, 'documents');
                    activity()
                        ->causedBy($user)
                        ->performedOn($transaction)
                        ->useLog('transactions')
                        ->withProperties(['Business_ID' => $business_id])
                        ->log('On Hold Sales added by API. Transaction ID:' . $transaction->id);

                    DB::commit();
                    Log::info("Transaction: Completed ");

                    if ($request->input('is_save_and_print') == 1) {
                        $url = $this->transactionUtil->getInvoiceUrl($transaction->id, $business_id);
                        return response(['url' => $url . '?print_on_load=true'], 200);
                    }

                    $msg     = trans("sale.pos_sale_added");
                    $receipt = '';

                    $print_invoice = false;
                    if (! $is_direct_sale) {
                        if ($input['status'] == 'draft') {
                            $msg = trans("sale.draft_added");

                            if ($input['is_quotation'] == 1) {
                                $msg           = trans("lang_v1.quotation_added");
                                $print_invoice = true;
                            }
                        } elseif ($input['status'] == 'final') {
                            $print_invoice = true;
                        }
                    }

                    if ($transaction->is_suspend == 1 && empty($pos_settings['print_on_suspend'])) {
                        $print_invoice = false;
                    }

                    if (($request->input('is_suspend'))) {
                        $print_invoice = true;
                    }

                    if ($print_invoice) {
                        $receipt = $this->receiptContentApi($business_id, $input['location_id'], $transaction->id, null, false, true);
                        /* Set qr code empty On Hold Sales */
                        if (isset($receipt['print']->qrcode)) {
                            $receipt['print']->qrcode = "";
                        }
                    }
                    $response = $this->orderService->order_status([
                        'orderId'   => $deliverect_order_id,
                        'status'    => 50, // preparing
                        'receiptId' => $receipt['print']->invoice_no,
                    ], $business_id, $pos_location);
                    $output = ['success' => 1, 'msg' => $msg, 'print_invoice' => $print_invoice, 'receipt' => $receipt];
                } else {
                    $output = [
                        'success' => 0,
                        'msg'     => trans("messages.something_went_wrong"),
                    ];
                }
                return response()->json($output, 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File: " . $e->getFile() . "\nLine:" . $e->getLine() . "\nMessage:" . $e->getMessage() . "\nuserID:" . $user_id . "\nbusinessID:" . $business_id, ['trace' => $e->getTraceAsString()]);

            //$msg = trans("messages.something_went_wrong");
            $msg = $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id;

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg'     => $msg,
                'receipt' => null,
            ];
            return response()->json($output, 500);
        }
    }

    private function receiptContentApi($business_id, $location_id, $transaction_id, $printer_type = null, $is_package_slip = false, $from_pos_screen = true, $invoice_layout_id = null, $user_id = null)
    {
        $output = [
            'is_enabled' => true,
            'print_type' => 'browser',
            'print'      => null,
        ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.

        //$output['is_enabled'] = true;

        $invoice_layout_id = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
        $invoice_layout    = $this->businessUtil->invoiceLayout($business_id, $location_id, $invoice_layout_id);

        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $business_id);

        /* Set Call code */
        $call_no                  = CallNumberScheme::callNumberGeneration($business_id, $location_id, $transaction_id, $user_id);
        $receipt_details->call_no = $call_no ?? "";

        $currency_details = [
            'symbol'             => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator'  => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;

        if ($is_package_slip) {
            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
            return $output;
        }

        //If print type App
        if ($receipt_printer_type == 'app') {
            $receiptData            = view('sale_pos.receipts.android_app_layout', compact('receipt_details'))->render();
            $receiptUrl             = getReceiptUrl($receiptData);
            $output['print']        = $receipt_details;
            $output['print_url']    = env('PUBLIC_HTML') . '/uploads/receipts/' . $receiptUrl;
            $output['html_content'] = $receiptData;
        }

        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type']     = 'printer';
            $output['print']          = $receipt_details;
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data']           = $receipt_details;
        } else {
            $layout          = ! empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';
            $output['print'] = $receipt_details;
        }

        return $output;
    }

    public function getSuspendedSells($user_id, $business_id, $pos_location, $deliverectOrderId)
    {
        try {
            $business_id = $business_id;
            $location_id = $pos_location;

            $user = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.view')) {
                    return ['success' => 0, 'errors' => 'Unauthorized  action! Not alowed view POS sell.'];
                }
            }

            $is_woocommerce           = $this->moduleUtil->isModuleInstalled('Woocommerce');
            $is_tables_enabled        = $this->commonUtil->isModuleEnabled('table_module', $business_id);
            $is_service_staff_enabled = $this->commonUtil->isModuleEnabled('service_staff_module', $business_id);

            $sells    = $this->transactionUtil->getListSells($business_id);
            $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id, $business_id);
            if (! empty($register)) {
                $created_date         = $register->created_at;
                $register->created_at = $register->created_at;
            } else {
                $created_date = Carbon::today()->toDateString() . ' 12:00:00';
            }
            //$created_date = "2024-06-04 00:00:00";
            $sells->where('transactions.created_at', '>=', $created_date);
            $sells->where('transactions.deliverect_order_id', $deliverectOrderId);

            $permitted_locations = $user->permitted_locations_api($business_id) ?? "";

            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            } else {
                //$sells->where('transactions.location_id', $location_id);
            }

            if ($user->can('sell.pos.add') && $user->can('sell.pos.view')) {
                $sells->where('transactions.created_by', $user_id);
            }

            if ($is_woocommerce) {
                $sells->addSelect('transactions.woocommerce_order_id');
                if (request()->only_woocommerce_sells) {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                }
            }

            $only_shipments = request()->only_shipments == 'true' ? true : [];
            if ($only_shipments && auth()->user()->can('access_shipping')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            $sells->groupBy('transactions.id');
            $sells->orderBy('transaction_date', 'desc');

            $sells->whereIn('transactions.payment_status', ['due']);

            request()->suspended = 1;
            if (! empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (! empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect(
                        'transactions.is_suspend',
                        'transactions.res_table_id',
                        'transactions.res_waiter_id',
                        'transactions.additional_notes'
                    )
                    ->get();

                $sql      = $sells->toSql();
                $bindings = $sells->getBindings();

                $salesIds = $sales->pluck('id')->toArray();

                $printer_type     = 'browser';
                $business_details = $this->businessUtil->getDetails($business_id);

                $print = [];
                foreach ($sales as $transaction) {
                    // $transaction['deliverectOrder'] = $this->orderService->get_new_deliverect_order(
                    //     $transaction->deliverect_order_id,
                    //     $business_id,
                    //     $pos_location
                    // );
                    $location_details = BusinessLocation::find($transaction->location_id);
                    // return response(['success' => 1, 'transaction' => $transaction], 200);

                    $invoice_layout_id = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
                    $invoice_layout    = $this->businessUtil->invoiceLayout($business_id, $transaction->location_id, $invoice_layout_id);

                    $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

                    $transaction['print']                        = $this->transactionUtil->getReceiptDetails($transaction->id, $transaction->location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $business_id);
                    $transaction['print']->call_no               = $transaction->call_no;
                    $transaction['print']->generatedBase64String = $transaction->generatedBase64String;
                    $transaction['print']->qrcode                = '';
                    $transaction['print']->created_at            = Carbon::parse($transaction->created_at)->format('Y-m-d H:i A');
                }
                if (count($sales) == 0) {
                    return [
                        'success' => 1,
                        'order'   => null,
                    ];
                } else {
                    return [
                        'success' => 1,
                        'order'   => $sales[0],
                    ];
                }
                // return ['success' => 1, 'sales' => $sales, 'is_service_staff_enabled' => $is_service_staff_enabled, 'transaction_sub_type' => $transaction_sub_type, 'salesIds' => $salesIds, 'bindings' => $bindings, 'sql' => $sql];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);
            //$msg = trans("messages.something_went_wrong");
            $msg    = "File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage();
            $output = [
                'success' => 0,
                'msg'     => $msg,
                'receipt' => null,
            ];
            return $output;
        }
    }

    public function onRejectOrder(Request $request, $business_id, $pos_location)
    {
        try {
            $orderId = $request->all()['order_id'];
            $this->orderService->order_status([
                'orderId' => $orderId,
                'status'  => 120,
            ], $business_id, $pos_location);
            return response()->json([
                "message" => "onRejectOrder: Order status updated to cancelled",
                "success" => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error occured while rejecting the order",
                "success" => false,
            ], 400);
        }
    }

    public function onReadyOrder(Request $request, $business_id, $pos_location)
    {
        try {
            $orderId = $request->all()['order_id'];
            $response = $this->orderService->order_status([
                'orderId' => $orderId,
                'status'  => 70,
            ], $business_id, $pos_location);
            Log::info('onReadyOrder: Order status updated to ready for pickup.');
            return response()->json(["message" => "Order status updated to ready for pickup", "success" => true], 200);
        
        } catch (\Exception $e) {
            Log::emergency("File: " . $e->getFile() . "\nLine:" . $e->getLine() . "\nMessage:" . $e->getMessage() . "\nuserID:" . $user_id . "\nbusinessID:" . $business_id, ['trace' => $e->getTraceAsString()]);
            return response()->json([
                "message" => "Error occured while updating the order status to ready",
                "success" => false,
            ], 400);
        }
    }

    public function onFinalizedOrder(Request $request, $business_id, $pos_location)
    {
        try {
            $orderId = $request->all()['order_id'];
            $this->orderService->order_status(['orderId' => $orderId,'status'  => 90,], $business_id, $pos_location);
            Log::info('onFinalizedOrder: Order status updated to finalized.');
            return response()->json([
                "message" => "Order status updated to finalized",
                "success" => true,
            ], 200);
        } catch (\Exception $e) {
            Log::emergency("File: " . $e->getFile() . "\nLine:" . $e->getLine() . "\nMessage:" . $e->getMessage() . "\nuserID:" . $user_id . "\nbusinessID:" . $business_id, ['trace' => $e->getTraceAsString()]);
            return response()->json(["message" => "Error occured while updating the order status to finalized", "success" => false,], 400);
        }
    }
}
