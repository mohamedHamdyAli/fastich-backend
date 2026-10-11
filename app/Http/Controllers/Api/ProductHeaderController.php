<?php
namespace App\Http\Controllers\Api;

use App\Account;
use App\Brands;
use App\BusinessLocation;
use App\CallNumberScheme;
use App\CashRegister;
use App\Category;
use App\ClientCompany;
use App\Contact;
use App\CustomerGroup;
use App\Http\Controllers\Controller;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\Media;
use App\OrderTypes;
use App\Product;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use App\Warranty;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Utils\ManufacturingUtil;

// use Salla\ZATCA\GenerateQrCode;
// use Salla\ZATCA\Tags\InvoiceDate;
// use Salla\ZATCA\Tags\InvoiceTaxAmount;
// use Salla\ZATCA\Tags\InvoiceTotalAmount;
// use Salla\ZATCA\Tags\Seller;

class ProductHeaderController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $commonUtil;
    protected $mfgUtil;

    /**
     * ProductHeaderController constructor.
     * @param  ContactUtil  $contactUtil
     * @param  ProductUtil  $productUtil
     * @param  BusinessUtil  $businessUtil
     * @param  TransactionUtil  $transactionUtil
     * @param  CashRegisterUtil  $cashRegisterUtil
     * @param  ModuleUtil  $moduleUtil
     * @param  NotificationUtil  $notificationUtil
     * @param  Util  $commonUtil
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil,
        Util $commonUtil,
        ManufacturingUtil $mfgUtil
    ) {
        $this->contactUtil      = $contactUtil;
        $this->productUtil      = $productUtil;
        $this->businessUtil     = $businessUtil;
        $this->transactionUtil  = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil       = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->commonUtil       = $commonUtil;
        $this->mfgUtil          = $mfgUtil;

        $this->dummyPaymentLine = [
            'method'        => 'cash', 'amount'    => 0, 'note'                 => '', 'card_transaction_number' => '',
            'card_number'   => '', 'card_type'     => '', 'card_holder_name'    => '', 'card_month'              => '', 'card_year' => '',
            'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return'     => 0, 'transaction_no' => '',
        ];
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */
    private function receiptContent($business_id, $location_id, $transaction_id, $printer_type = null, $is_package_slip = false, $from_pos_screen = true, $invoice_layout_id = null, $user_id = null)
    {
        $output = [
            //            'is_enabled' => false,
            'print_type' => 'browser',
            'print'      => null,
            //                   'printer_config' => [],
            //                   'data' => []
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

        $call_no = CallNumberScheme::callNumberGeneration($business_id, $location_id, $transaction_id, $user_id);

        $receipt_details->call_no = $call_no;

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
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'app') {
            $receiptData            = view('sale_pos.receipts.android_app_layout', compact('receipt_details'))->render();
            $receiptUrl             = getReceiptUrl($receiptData);
            $output['print_type']   = 'app';
            $output['print']        = $receipt_details;
            $output['print_url']    = env('PUBLIC_HTML') . '/uploads/receipts/' . $receiptUrl;
            $output['html_content'] = $receiptData;
        } else if ($receipt_printer_type == 'printer') {
            $output['print_type']     = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data']           = $receipt_details;
            $output['print']          = $receipt_details;
        } elseif ($receipt_printer_type == 'browser') {
            $output['print_type']     = 'browser';
            $output['print']          = $receipt_details;
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data']           = $receipt_details;
        } else {
            $output['print_type'] = 'else';
            //$layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';
            $output['print'] = $receipt_details;
        }

        return $output;
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getSuspendedSells(Request $request)
    {
        if (! auth()->user()->can('sell.pos.view')) {
            return response(['errors' => 'Unauthorized action! Not alowed to view POS sell.'], 403);
        }
        if (! auth()->user()->can('sell.pos.add')) {
            return response(['errors' => 'Unauthorized action! Not alowed to add POS sell.'], 403);
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id     = $request->session()->get('user.id');

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

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! auth()->user()->can('sell.pos.view')) {
                $sells->where('transactions.created_by', request()->session()->get('user.id'));
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
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                $sql      = $sells->toSql();
                $bindings = $sells->getBindings();

                $salesIds = $sales->pluck('id')->toArray();

                $printer_type     = 'browser';
                $business_details = $this->businessUtil->getDetails($business_id);
                //return response()->json(['sales' => $sales], 200);

                $print = [];
                foreach ($sales as $transaction) {

                    $location_details  = BusinessLocation::find($transaction->location_id);
                    $invoice_layout_id = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
                    $invoice_layout    = $this->businessUtil->invoiceLayout($business_id, $transaction->location_id, $invoice_layout_id);

                    $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

                    $transaction['print']                        = $this->transactionUtil->getReceiptDetails($transaction->id, $transaction->location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $business_id);
                    $transaction['print']->call_no               = $transaction->call_no;
                    $transaction['print']->generatedBase64String = $transaction->generatedBase64String;
                    $transaction['print']->qrcode                = '';
                    $transaction['print']->created_at            = Carbon::parse($transaction->created_at)->format('Y-m-d H:i A');

                }

                return response([
                    'sales'                    => $sales,
                    'is_tables_enabled'        => $is_tables_enabled,
                    'is_service_staff_enabled' => $is_service_staff_enabled,
                    'transaction_sub_type'     => $transaction_sub_type,
                    'salesIds'                 => $salesIds,
                    'bindings'                 => $bindings,
                    'sql'                      => $sql,
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);
            //$msg = trans("messages.something_went_wrong");
            $msg    = "File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage();
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response([
                'msg'                      => $msg,
                'sales'                    => null,
                'is_tables_enabled'        => false,
                'is_service_staff_enabled' => false,
                'transaction_sub_type'     => null,
                'salesIds'                 => null,
                'bindings'                 => $bindings,
                'sql'                      => $sql,
            ], 500);

        }
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function getRegisterDetails()
    {
        if (! auth()->user()->can('sell.pos.view_cash_register')) {
            return response(['errors' => 'Unauthorized action! Not alowed to view cash register.'], 422);
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details_array = $this->cashRegisterUtil->getRegisterDetails(null, $business_id);

        $register_details = $register_details_array['register_details'];

        $user_id    = auth()->user()->id;
        $open_time  = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->commonUtil->isModuleEnabled('direct_sales_module', $business_id);

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time,
            $is_types_of_service_enabled);

        $payment_types                      = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);
        $register_details['close_time']     = $close_time;
        $total_card                         = $register_details['total_card'] ?? 0.00;
        $cash_in_hand                       = $register_details['cash_in_hand'] ?? 0.00;
        $total_prepaid                      = $register_details['total_prepaid'] ?? 0.00;
        $all_card_sales                     = ($total_card + $total_prepaid) ?? 0.00;
        $register_details['all_card_sales'] = $all_card_sales;
        $total_sale                         = $register_details['total_sale'] ?? 0.00;
        $total_cash_refund                  = $register_details['total_cash_refund'] ?? 0.00;
        $total_card_refund                  = $register_details['total_card_refund'] ?? 0.00;
        $total_refund                       = $register_details['total_refund'] ?? 0.00;
        $total_payment                      = $register_details['total_payment'] ?? 0.00;
        $total_sales                        = $details['transaction_details']['total_sales'] ?? 0.00;
        $total_refund_cash_new              = ($total_sale - $total_sales) ?? 0.00;
        $total_payment_new                  = ($total_payment) ?? 0.00;
        $net_payment_new                    = ($total_payment_new - $total_refund) ?? 0.00;

        $total_cash     = $register_details['total_cash'] ?? 0.00;
        $total_app_cash = $register_details['total_app_cash'] ?? 0.00;
        $all_cash_new   = ($total_cash + $total_app_cash) ?? 0.00;

        $total_app_card = $register_details['total_app_card'] ?? 0.00;
        $all_card_new   = ($total_card + $total_app_card) ?? 0.00;

        $clientApp      = ClientCompany::where('business_id', $business_id)->get();
        $clientAppArray = [];
        if (! empty($clientApp)) {

            foreach ($clientApp as $key => $app) {
                $app_name_tite                               = $app['app_name'] ?? "";
                $app_name                                    = str_replace(' ', '', $app['app_name']) ?? "";
                $clientAppArray[$key]['app_prepaid_tite']    = "Prepaid " . $app_name_tite . " - مدفوع" . ":";
                $clientAppArray[$key]['app_cash_tite']       = "Cash " . $app_name_tite . " - كاش" . ":";
                $clientAppArray[$key]['app_card_tite']       = "Card " . $app_name_tite . " - شبكه" . ":";
                $clientAppArray[$key]['total_card_value']    = $register_details['total_card_' . $app_name];
                $clientAppArray[$key]['total_cash_value']    = $register_details['total_cash_' . $app_name];
                $clientAppArray[$key]['total_prepaid_value'] = $register_details['total_prepaid_' . $app_name];

            }
        }
        $register_details['net_payment_new']       = $net_payment_new;
        $register_details['total_payment_new']     = $total_payment_new;
        $register_details['total_refund_cash_new'] = $total_refund;
        $register_details['clientAppArray']        = $clientAppArray;
        $register_details['all_cash_new']          = $all_cash_new;
        $register_details['all_card_new']          = $all_card_new;

        return response([
            'register_details' => $register_details, 'details' => $details, 'payment_types'                  => $payment_types,
            'close_time'       => $close_time, 'sql'           => $register_details_array['sql'], 'bindings' => $register_details_array['bindings'], "appClientNameArray" => $register_details_array['appClientNameArray'], "appClientName" => $register_details_array['appClientName'],
        ], 200);
    }

    /**
     * @param $id
     *
     * @return Application|ResponseFactory|Response
     */
    public function getCloseRegister($id = null)
    {
        if (! auth()->user()->can('sell.pos.close_cash_register')) {
            return response(['errors' => 'Unauthorized action! Not alowed to Close the register.'], 422);
        }

        $business_id = request()->session()->get('user.business_id');
        //$register_details = $this->cashRegisterUtil->getRegisterDetails($id);
        $register_details_array = $this->cashRegisterUtil->getRegisterDetails($id);

        $register_details = $register_details_array['register_details'];

        $user_id    = $register_details->user_id;
        $open_time  = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->commonUtil->isModuleEnabled('direct_sales_module', $business_id);

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time,
            $is_types_of_service_enabled);

        $payment_types                  = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);
        $register_details['close_time'] = $close_time;

        return response([
            'register_details' => $register_details, 'details' => $details, 'payment_types' => $payment_types,
        ], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function closeRegister(Request $request)
    {
        if (! auth()->user()->can('sell.pos.close_cash_register')) {
            return response(['errors' => 'Unauthorized action! Not alowed to Close the register.'], 422);
        }

        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = [
                    'success' => 0,
                    'msg'     => 'Feature disabled in demo!!',
                ];

                return response(['url' => action('HomeController@index'), 'status' => $output]);
            }

            $input                           = $request->only(['closing_amount', 'total_expense', 'remainder_cash_in_hand', 'total_card_slips', 'total_cheques', 'closing_note']);
            $input['closing_amount']         = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $input['total_expense']          = $this->cashRegisterUtil->num_uf($input['total_expense']);
            $input['remainder_cash_in_hand'] = $this->cashRegisterUtil->num_uf($input['remainder_cash_in_hand']);
            $user_id                         = $request->input('user_id');
            $input['closed_at']              = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status']                 = 'close';

            CashRegister::where('user_id', $user_id)
                ->where('status', 'open')
                ->update($input);
            $output = [
                'success' => 1,
                'msg'     => __('cash_register.close_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => __("messages.something_went_wrong"),
            ];
        }

        return response($output);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function deleteSuspendedSales(Request $request)
    {
        if (! auth()->user()->can('sell.pos.edit')) {
            return response(['errors' => 'Unauthorized action! Not alowed to edit POS sell.'], 422);
        }
        $transaction_id = $request->get('sell_id');
        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id     = $request->session()->get('user.id');

            if (! empty($transaction_id) && ! empty($business_id)) {
                //Begin transaction
                DB::beginTransaction();
                $transaction = Transaction::where('id', $transaction_id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines', 'payment_lines'])
                    ->first();
                $location_id  = $transaction->location_id;
                $sell_details = TransactionSellLine::with([
                    'product', 'product.variations', 'product.modifier_sets.variations', 'variations', 'modifiers',
                ])->where('transaction_id', $transaction_id)->where('parent_sell_line_id', '=', null)->get();
                if (! empty($sell_details)) {
                    foreach ($sell_details as $lineObj) {
                        $enable_stock = $lineObj->product->enable_stock;
                        if (! empty($lineObj->id) && ! empty($lineObj->quantity)) {
                            TransactionSellLine::where('id', $lineObj->id)->update(['quantity_returned' => $lineObj->quantity]);
                            /* $decrease_qty = ($lineObj->quantity);
                            if ($enable_stock) {
                                $this->productUtil->decreaseProductQuantity($lineObj->product_id, $itemObj->variation_id, $location_id, $decrease_qty);
                            } else {
                                $recipe = MfgRecipe::where('variation_id', $lineObj->variation_id)
                                    ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                        $query->orderBy('sort_order', 'asc');
                                    }])->first();
                                if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($decrease_qty)) {
                                    $res = $this->getDecreaseInventory($recipe, $business_id, $location_id, $decrease_qty);
                                }
                                if (! empty($lineObj->modifiers)) {
                                    foreach ($lineObj->modifiers as $key => $modifierObj) {
                                        if (! empty($modifierObj->id) && ! empty($modifierObj->quantity)) {
                                            TransactionSellLine::where('id', $modifierObj->id)->update(['quantity_returned' => $modifierObj->quantity]);
                                            $recipeModifier = MfgRecipe::where('variation_id', $modifierObj->variation_id)
                                                ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                                    $query->orderBy('sort_order', 'asc');
                                                }])->first();
                                            $decrease_modifiers_inventory_qty = ($modifierObj->quantity);
                                            if (! empty($recipeModifier) && ! empty($business_id) && ! empty($location_id) && ! empty($decrease_modifiers_inventory_qty)) {
                                                $this->getDecreaseInventory($recipeModifier, $business_id, $location_id, $decrease_modifiers_inventory_qty);
                                            }
                                        }
                                    }
                                }
                            } */
                        }
                    }
                }

                if (! empty($transaction->final_total)) {
                    /* $payment[0] = [
                        'amount' => $transaction->final_total,
                        'method' => 'cash',
                    ]; */
                    //Add payments to Cash Register & Transactions payment
                    /* $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payment, $business_id, $user_id);
                    $this->cashRegisterUtil->addSellPayments($transaction, $payment);
                    Transaction::where('id', $transaction_id)->update(['is_suspend' => 0]); */
                }

                //$output = $this->transactionUtil->deleteSale($business_id, $transaction_id);
                $output = $this->transactionUtil->returnSale($business_id, $transaction_id);
                if (is_object($output['data'])) {
                    activity()
                        ->performedOn($output['data'])
                        ->useLog('transactions')
                        ->log('Transaction returned on Hold.Transaction ID:' . $output['data']->id . ' & business ID:' . $business_id);
                }
                DB::commit();
            } else {
                return response(['success' => 0, 'msg' => __('sale.no_transaction_id')], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output['success'] = false;
            //$output['msg'] = trans("messages.something_went_wrong");
            $output['msg'] = $e->getMessage();
        }
        return response(['data' => $output], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|Response
     */
    public function getDecreaseInventory($recipe, $business_id, $location_id, $product)
    {
        try {
            if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($product)) {
                $ingredients = $this->mfgUtil->getIngredientDetails($recipe, $business_id, $location_id);
                foreach ($ingredients as $ingredient) {
                    if ($ingredient['enable_stock']) {
                        $ingredient_quantity = $this->mfgUtil->calc_percentage($ingredient['quantity'], $ingredient['waste_percent'], $ingredient['quantity']);
                        $this->productUtil->decreaseProductQuantity($ingredient['product_id'], $ingredient['variation_id'], $location_id, $ingredient_quantity * $product['quantity']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
        }

    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function deleteAllSuspendedSales(Request $request)
    {
        if (! auth()->user()->can('sell.pos.return')) {
            $output['success'] = false;
            $output['msg']     = "Unauthorized action! Not alowed to return POS sell.";
            return response(['data' => $output], 422);
        }

        $output['success'] = false;
        $output['msg']     = "Unauthorized action";
        return response(['data' => $output], 422);

        try {
            $business_id = request()->session()->get('user.business_id');
            //Begin transaction
            DB::beginTransaction();

            $input          = $request->all();
            $transactionIds = explode(',', $input['value']);
            foreach ($transactionIds as $item) {
                //$output = $this->transactionUtil->deleteSale($business_id, $item);
                $output = $this->transactionUtil->returnSale($business_id, $item);

            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg']     = trans("messages.something_went_wrong");
        }

        return response(['data' => $output], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function editSuspendedSales(Request $request)
    {
        $id = $request->get('transaction_id');

        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('sell.pos.edit'))) {
            return response(['errors' => 'Unauthorized action! Not alowed to edit POS sell.'], 422);
        }

        //Check if the transaction can be edited or not.
        /*  $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
        return response([
        'success' => 0, 'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]),
        ], 422);
        } */

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return response()->json(['success' => 0, 'msg' => __('cash_register.register_closed'), 'url' => 'CashRegisterController@create'], 403);
            return response(['url' => 'CashRegisterController@create'], 200);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return response(['success' => 0, 'msg' => __('lang_v1.return_exist')], 422);
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);

        $allTaxDropdown = TaxRate::where('business_id', $business_id)->get();
        $quantity       = request()->get('quantity', 1);

        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->with(['price_group', 'types_of_service'])
            ->findorfail($id);
        //return response($transaction, 200);

        $location_id           = $transaction->location_id;
        $business_location     = BusinessLocation::find($location_id);
        $payment_types         = $this->productUtil->payment_types($business_location, true);
        $location_printer_type = $business_location->receipt_printer_type;

        $sell_details = TransactionSellLine::with([
            'product', 'product.variations', 'product.modifier_sets.variations', 'variations', 'modifiers',
        ])->where('transaction_id', $transaction->id)->where('parent_sell_line_id', '=', null)->get();
        $featured_products = $business_location->getFeaturedProducts();
        //return response($sell_details, 200);
        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $shortcuts    = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings,
            true);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent    = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id, $location_id) : false;
        $brands     = (request()->session()->get('business.enable_brand') == 1) ? Brands::forDropdown($business_id)
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->commonUtil->isModuleEnabled('payment_account_module', $business_id)) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        $waiters = [];
        if ($this->commonUtil->isModuleEnabled('service_staff_module', $business_id) && ! empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters         = $this->productUtil->serviceStaffDropdown($business_id);
        }
        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount     = auth()->user()->can('sell.pos.add_product_discount_from_pos_screen');
        $edit_price        = auth()->user()->can('sell.pos.edit_product_price_from_pos_screen');
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $warranties = $this->__getwarranties();
        $sub_type   = request()->get('sub_type');

        //pos screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_pos_screen_view', ['sub_type' => $sub_type]);

        $invoice_schemes         = [];
        $default_invoice_schemes = null;

        if ($transaction->status == 'draft') {
            $invoice_schemes         = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $invoice_layouts = InvoiceLayout::forDropdown($business_id);

        $originalData = [
            'all_tax_dropdown' => $allTaxDropdown, 'payment_types'            => $payment_types,
            'walk_in_customer' => $walk_in_customer, 'product'                => $sell_details, 'transaction'    => $transaction,
            'payment_lines'    => $payment_lines, 'commission_agent'          => $commission_agent, 'categories' => $categories,
            'pos_settings'     => $pos_settings, 'change_return'              => $change_return, 'types'         => $types,
            'customer_groups'  => $customer_groups, 'brands'                  => $brands, 'accounts'             => $accounts,
            'waiters'          => $waiters, 'redeem_details'                  => $redeem_details, 'edit_price'   => $edit_price,
            'edit_discount'    => $edit_discount, 'shipping_statuses'         => $shipping_statuses,
            'warranties'       => $warranties, 'sub_type'                     => $sub_type, 'pos_module_data'    => $pos_module_data,
            'invoice_schemes'  => $invoice_schemes, 'default_invoice_schemes' => $default_invoice_schemes,
            'invoice_layouts'  => $invoice_layouts, 'featured_products'       => $featured_products,
        ];

        $discount              = null;
        $output['product_row'] = [
            'product'    => $sell_details, 'all_tax_dropdown' => $allTaxDropdown, 'pos_settings' => $pos_settings,
            'discount'   => $discount, 'waiters'              => $waiters, 'edit_discount'       => $edit_discount,
            'edit_price' => $edit_price, 'warranties'         => $warranties,
        ];

        $enabledTax = false;
        if ($request->session()->get('business.enable_inline_tax') == 1) {
            $enabledTax = true;
        }
        $common_settings     = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;

        $products      = [];
        $modifierTaxes = [];
        foreach ($sell_details as $key => $sell_detail) {
            $tax_dropdown = null;

            if (! empty($sell_detail->tax_id) && $sell_detail->product->tax_type == 'inclusive') {
                $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $sell_detail->tax_id)->first();
            }
            $data['product_row']                       = $sell_detail->product;
            $data['product_row']['product_id']         = $sell_detail->product->id;
            $data['product_row']['product_name']       = $sell_detail->product->name;
            $data['product_row']['contact_id']         = $transaction->contact_id;
            $data['product_row']['customer_name']      = $transaction->contact->name;
            $data['product_row']['product_type']       = $sell_detail->product->type;
            $data['product_row']['tax_id']             = $sell_detail->tax_id;
            $data['product_row']['quantity']           = $sell_detail->quantity;
            $data['product_row']['tax_dropdown']       = $tax_dropdown;
            $data['product_row']['all_tax_dropdown']   = $allTaxDropdown;
            $data['product_row']['enabled_tax']        = $enabledTax;
            $data['product_row']['enabled_warranties'] = $is_warranty_enabled;
            $data['product_row']['pos_settings']       = $pos_settings;
            $data['product_row']['discount']           = $discount;
            $data['product_row']['waiters']            = $waiters;
            $data['product_row']['edit_discount']      = $edit_discount;
            $data['product_row']['edit_price']         = $edit_price;
            $data['product_row']['warranties']         = $warranties;
            $data['modifier_products']                 = [];
            $data['modifiers']                         = [];

            // Product Discount
            $is_cg                           = ! empty($cg->id) ? true : false;
            $is_pg                           = ! empty($price_group) ? true : false;
            $data['product_row']['discount'] = $this->productUtil->getProductDiscount($sell_detail->product, $business_id, $location_id, $is_cg, $is_pg, $data['product_row']['variation_id']);

            if ($data['product_row']['discount'] == null) {
                $data['product_row']['discount'] = [
                    'id'              => null,
                    'name'            => null,
                    "discount_type"   => null,
                    "discount_amount" => null,
                ];
            } else {
                //$data['product_row']['discount']['discount_type'] = $sell_detail->line_discount_type;
                //$data['product_row']['discount']['discount_amount'] = $sell_detail->line_discount_amount;
            }

            // Selector Modifier
            $selectorModifier = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->id)
                ->where('children_type', 'modifier')->get();

            $modifiers_ids = [];
            if (count($selectorModifier) > 0) {
                $sell_details[$key]->modifiers = $selectorModifier;
                foreach ($selectorModifier as $sell_line_modifier) {
                    $modifiers_ids[] = $sell_line_modifier->variation_id;
                }
            }
            $data['modifiers'] = Variation::whereIn('id', $modifiers_ids)->get();
            foreach ($data['modifiers'] as $key => $modifier) {
                if (! is_null($modifier->tax_id) && $modifier->tax_type == 'inclusive') {
                    $taxRate                                 = TaxRate::where('id', $modifier->tax_id)->first();
                    $data['modifiers'][$key]['tax_dropdown'] = $taxRate;
                    array_push($modifierTaxes,
                        $sell_detail->quantity * ($modifier->default_sell_price * $taxRate->amount / 100));
                } else {
                    $data['modifiers'][$key]['tax_dropdown'] = null;
                }
            }

            //Product Variations
            foreach ($sell_detail->product->variations as $variation) {
                $data['product_row']['product_variation_name'] = $variation->name;
                $data['product_row']['variation_name']         = $variation->name;
                $data['product_row']['variation_id']           = $variation->id;
                $data['product_row']['default_sell_price']     = $variation->default_sell_price;
                $data['product_row']['sell_price_inc_tax']     = $variation->sell_price_inc_tax;
                $data['product_row']['quantity']               = $sell_detail->quantity;
                $data['product_row']['unit_price_inc_tax']     = $sell_detail->unit_price_inc_tax;
                $data['product_row']['unit_price']             = $sell_detail->unit_price;
                $data['product_row']['sell_line_note']         = $sell_detail->sell_line_note;
            }

            if (! empty($sell_detail->product->modifier_sets)) {
                $modifierProducts = $sell_detail->product->modifier_sets;
                foreach ($modifierProducts as $modifierProduct) {
                    foreach ($modifierProduct->variations as $modifier) {
                        $data['modifier_products']['sub_modifier'] = $modifier;
                    }
                }
                $data['modifier_products'] = $modifierProducts;
            }
            $data['product_row']['modifier_tax'] = array_sum($modifierTaxes);
            //$data['product_row'] = $data['product_row']['product'];
            $products[] = $data['product_row'];

        }
        $order_type = [];
        //Show Order Types
        $order_types                     = OrderTypes::find($transaction->order_type_Id);
        $output['order_type_name_label'] = trans("receipt.order_type_label");
        if ($order_types) {
            $order_type_name          = $order_types->order_name ?? "";
            $order_type['id']         = (int) $transaction->order_type_Id ?? "";
            $order_type['order_name'] = trans("receipt." . $order_type_name) ?? $order_type_name;

        } else {
            $order_type['id']         = "";
            $order_type['order_name'] = "";
        }

        $items = [
            'transaction_id' => $transaction->id,
            'order_type'     => $order_type,
            'products'       => $products,
        ];

        return response($items, 200);
    }

    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|RedirectResponse|Response
     */
    public function updateSuspendedSales(Request $request)
    {
        if (! auth()->user()->can('sell.pos.edit')) {
            return response(['errors' => 'Unauthorized action! Not alowed to edit POS sell.'], 403);
        }
        if (! auth()->user()->can('sell.pos.view')) {
            return response(['errors' => 'Unauthorized action! Not alowed to view POS sell.'], 403);
        }

        $id          = $request->get('transaction_id');
        $business_id = $request->session()->get('user.business_id');
        $user_id     = $request->session()->get('user.id');

        try {
            $input = $request->except('_token');

            //status is send as quotation from edit sales screen.
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status']       = 'draft';
                $input['is_quotation'] = 1;
            }
            $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

            $is_direct_sale = false;
            if (! empty($input['products'])) {
                //Get transaction value before updating.
                $transaction_before = Transaction::find($id);
                $status_before      = $transaction_before->status;
                $rp_earned_before   = $transaction_before->rp_earned;
                $rp_redeemed_before = $transaction_before->rp_redeemed;

                if ($transaction_before->is_direct_sale == 1) {
                    $is_direct_sale = true;
                }

                // Contact Id
                $contact_id = $request->get('contact_id', null);
                if ($contact_id == 'no_customer_select') {
                    $contact_id = Contact::where('business_id', $business_id)->first()->id;
                }
                $input['contact_id'] = $contact_id;

                //Check Customer credit limit
                /*  $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id);

                if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = ['success' => 0,
                'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                'receipt' => null,
                ];
                if (!$is_direct_sale) {
                return $output;
                } else {
                return response(['url' => 'SellController@index', 'status' => $output]);
                }
                } */

                //Check if there is a open register, if no then redirect to Create Register screen.
                if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return response()->json(['success' => 0, 'msg' => __('cash_register.register_closed'), 'url' => 'CashRegisterController@create'], 403);
                    return response(['url' => 'CashRegisterController@create']);
                }

                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount'            => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                if (! empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $input['commission_agent'] = ! empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
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

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = ! empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                if ($status_before == 'draft' && ! empty($request->input('invoice_scheme_id'))) {
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
                $input['clientCompany'] = request()->get('clientCompany') ?? "";
                $input['order_type_Id'] = request()->get('order_type_Id') ?? "";

                //Begin transaction
                DB::beginTransaction();

                $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);

                //Update Sell lines
                $deleted_lines = $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], true, $status_before, [], true, $enabled_modules, $business_id);

                //Update update lines
                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                if (! $is_direct_sale && ! $transaction->is_suspend && ! $is_credit_sale) {
                    //Add change return
                    $change_return              = $this->dummyPaymentLine;
                    $change_return['amount']    = $input['change_return'];
                    $change_return['is_return'] = 1;
                    if (! empty($input['change_return_id'])) {
                        $change_return['id'] = $input['change_return_id'];
                    }
                    $input['payment'][] = $change_return;

                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);

                    //Update cash register
                    $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                }

                if ($request->session()->get('business.enable_rp') == 1) {
                    $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, $rp_earned_before, $transaction->rp_redeemed, $rp_redeemed_before);
                }

                //Update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                //Update product stock
                $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);

                //Allocate the quantity from purchase and add mapping of
                //purchase & sell lines in
                //transaction_sell_lines_purchase_lines table
                $business_details = $this->businessUtil->getDetails($business_id);
                $pos_settings     = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                $business = ['id'   => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id'       => $input['location_id'],
                    'pos_settings'      => $pos_settings,
                ];
                $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);

                $log_properties = [];
                if (isset($input['repair_completed_on'])) {
                    $completed_on = ! empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                    if ($transaction->repair_completed_on != $completed_on) {
                        $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                        $log_properties['completed_on_to']   = $completed_on;
                    }
                }

                //Set Module fields
                if (! empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                activity()
                    ->performedOn($transaction)
                    ->useLog('transactions')
                    ->log('On Hold sales updated. Transaction ID:' . $transaction->id);

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);
                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg     = '';
                $receipt = '';

                $invoice_layout_id = $request->input('invoice_layout_id');

                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_updated");
                    if (! $is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_hold_order_updated");
                    if (! $is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                    } else {
                        $receipt = '';
                    }
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];
            } else {
                $output = ['success' => 0,
                    'msg'                => trans("messages.something_went_wrong"),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);

            $output = ['success' => 0,
                'msg'                => __('messages.something_went_wrong'),
            ];
        }

        if (! $is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return response(['url' => 'SellController@getQuotations', 'status' => $output]);
                } else {
                    return response(['url' => 'SellController@getDrafts', 'status' => $output]);
                }
            } else {
                if (! empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return response(['url' => '\Modules\Repair\Http\Controllers\RepairController@index', 'status' => $output]);
                }
                return response(['url' => 'SellController@index', 'status' => $output]);
            }
        }
    }

    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|RedirectResponse|Response
     */
    public function updateFinalizePayment(Request $request)
    {
        if (! auth()->user()->can('sell.pos.edit')) {
            return response(['errors' => 'Unauthorized action! Not alowed to edit POS sell.'], 403);
        }
        if (! auth()->user()->can('sell.pos.view')) {
            return response(['errors' => 'Unauthorized action! Not alowed to view POS sell.'], 403);
        }

        $id          = $request->get('transaction_id');
        $user_id     = $request->session()->get('user.id');
        $business_id = $request->session()->get('user.business_id');

        try {
            $input = $request->except('_token');

            //status is send as quotation from edit sales screen.
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status']       = 'draft';
                $input['is_quotation'] = 1;
            }

            $location_id     = $input['location_id'] ?? "";
            $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

            $is_direct_sale = false;
            if (! empty($input['products'])) {
                //Get transaction value before updating.
                $transaction_before = Transaction::find($id);
                $status_before      = $transaction_before->status;
                $rp_earned_before   = $transaction_before->rp_earned;
                $rp_redeemed_before = $transaction_before->rp_redeemed;

                if ($transaction_before->is_direct_sale == 1) {
                    $is_direct_sale = true;
                }

                // Contact Id
                $contact_id = $request->get('contact_id', null);
                if ($contact_id == 'no_customer_select') {
                    $contact_id = Contact::where('business_id', $business_id)->first()->id;
                }
                $input['contact_id'] = $contact_id;

                //Check Customer credit limit
                /* $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id);

                if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = ['success' => 0,
                'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                'receipt' => null,
                ];
                if (!$is_direct_sale) {
                return $output;
                } else {
                return response(['url' => 'SellController@index', 'status' => $output]);
                }
                } */

                //Check if there is a open register, if no then redirect to Create Register screen.
                if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return response()->json(['success' => 0, 'msg' => __('cash_register.register_closed'), 'url' => 'CashRegisterController@create'], 403);
                    return response(['url' => 'CashRegisterController@create']);
                }

                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount'            => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                if (! empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $input['commission_agent'] = ! empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
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

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = ! empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                if ($status_before == 'draft' && ! empty($request->input('invoice_scheme_id'))) {
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
                $input['clientCompany'] = request()->get('clientCompany') ?? "";
                $input['order_type_Id'] = request()->get('order_type_Id') ?? "";

                //Begin transaction
                DB::beginTransaction();
                //Check for final and do some processing.
                if (isset($input['status']) && ($input['status'] == 'final')) {

                    $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);

                    //Update Sell lines
                    $deleted_lines = $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], true, $status_before, [], true, $enabled_modules, $business_id);

                    //Update update lines
                    $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                    //Add payments to Cash Register
                    if (! $is_direct_sale && ! $transaction->is_suspend && ! empty($input['payment']) && ! $is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'], $input['clientCompany']);
                    }

                    if (! $is_direct_sale && ! $transaction->is_suspend && ! $is_credit_sale) {
                        //Add change return
                        $change_return              = $this->dummyPaymentLine;
                        $change_return['amount']    = $input['change_return'];
                        $change_return['is_return'] = 1;
                        if (! empty($input['change_return_id'])) {
                            $change_return['id'] = $input['change_return_id'];
                        }
                        $input['payment'][] = $change_return;

                        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);

                        //Update cash register
                        $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                    }

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, $rp_earned_before, $transaction->rp_redeemed, $rp_redeemed_before);
                    }

                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    //Update product stock
                    $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings     = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = ['id'   => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id'       => $input['location_id'],
                        'pos_settings'      => $pos_settings,
                    ];
                    $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);

                    $log_properties = [];
                    if (isset($input['repair_completed_on'])) {
                        $completed_on = ! empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                        if ($transaction->repair_completed_on != $completed_on) {
                            $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                            $log_properties['completed_on_to']   = $completed_on;
                        }
                    }

                    //update stock.
                    if (isset($input['products'])) {
                        foreach ($input['products'] as $product) {
                            //return response()->json(['success' => 0, 'product' => $product], 200);

                            $decrease_qty = $this->productUtil
                                ->num_uf($product['quantity']);
                            if (! empty($product['base_unit_multiplier'])) {
                                $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                            }
                            if ($product['enable_stock']) {
                                $this->productUtil->decreaseProductQuantity(
                                    $product['product_id'],
                                    $product['variation_id'],
                                    $location_id,
                                    $decrease_qty
                                );
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
                            //Decrease quantity of combo as well.
                            if ($product['product_type'] == 'combo') {
                                $this->productUtil
                                    ->decreaseProductQuantityCombo(
                                        $product['combo'],
                                        $input['location_id']
                                    );
                            }
                        }
                    }
                }

                //Set Module fields
                if (! empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                activity()
                    ->performedOn($transaction)
                    ->useLog('transactions')
                    ->log('Sales updated. Transaction ID:' . $transaction->id);

                // Delete Suspend sells
                //                if (!empty($request->get('transactionId'))){
                //                    $transaction = Transaction::find($id);
                //                    $transaction->delete();
                //                }

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);
                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg     = '';
                $receipt = '';

                $invoice_layout_id = $request->input('invoice_layout_id');

                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_updated");
                    if (! $is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id, $user_id);
                    } else {
                        $receipt = null;
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_sale_updated");
                    if (! $is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id, $user_id);
                    } else {
                        $receipt = null;
                    }
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];
            } else {
                $output = ['success' => 0,
                    'msg'                => trans("messages.something_went_wrong"),
                    'receipt'            => null,
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);

            $output = ['success' => 0,
                'msg'                => __('messages.something_went_wrong'),
                'receipt'            => null,
            ];
        }

        if (! $is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return response(['url' => 'SellController@getQuotations', 'status' => $output]);
                } else {
                    return response(['url' => 'SellController@getDrafts', 'status' => $output]);
                }
            } else {
                if (! empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return response(['url' => '\Modules\Repair\Http\Controllers\RepairController@index', 'status' => $output]);
                }
                return response(['url' => 'SellController@index', 'status' => $output]);
            }
        }
    }

    private function __getwarranties()
    {
        $business_id         = session()->get('user.business_id');
        $common_settings     = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties          = $is_warranty_enabled ? Warranty::where('business_id', $business_id)
            ->get() : [];

        return $warranties;
    }

    /**
     * Parse the weighing barcode.
     *
     * @return array
     */
    private function __parseWeighingBarcode($scale_barcode)
    {
        $business_id = session()->get('user.business_id');

        $scale_setting = session()->get('business.weighing_scale_setting');

        $error_msg = trans("messages.something_went_wrong");

        //Check for prefix.
        if ((strlen($scale_setting['label_prefix']) == 0) || Str::startsWith($scale_barcode,
            $scale_setting['label_prefix'])) {
            $scale_barcode = substr($scale_barcode, strlen($scale_setting['label_prefix']));

            //Get product sku, trim left side 0
            $sku = ltrim(substr($scale_barcode, 0, $scale_setting['product_sku_length'] + 1), '0');

            //Get quantity integer
            $qty_int = substr($scale_barcode, $scale_setting['product_sku_length'] + 1,
                $scale_setting['qty_length'] + 1);

            //Get quantity decimal
            $qty_decimal = '0.' . substr($scale_barcode,
                $scale_setting['product_sku_length'] + $scale_setting['qty_length'] + 2,
                $scale_setting['qty_length_decimal'] + 1);

            $qty = (float) $qty_int + (float) $qty_decimal;

            //Find the variation id
            $result = $this->productUtil->filterProduct($business_id, $sku, null, false, null, [], ['sub_sku'], false,
                'exact')->first();

            if (! empty($result)) {
                return [
                    'variation_id' => $result->variation_id,
                    'qty'          => $qty,
                    'success'      => true,
                ];
            } else {
                $error_msg = trans("lang_v1.sku_not_match", ['sku' => $sku]);
            }
        } else {
            $error_msg = trans("lang_v1.prefix_did_not_match");
        }

        return [
            'success' => false,
            'msg'     => $error_msg,
        ];
    }
}
