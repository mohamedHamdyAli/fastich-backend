<?php
namespace App\Http\Controllers\Api;

use App\Business;
use App\BusinessLocation;
use App\CallNumberScheme;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Media;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Transaction;
use App\TransactionSellLine;
use App\TypesOfService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Warranty;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
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
// use Salla\ZATCA\Tags\TaxNumber;
use Spatie\Permission\Models\Role;

class ProductDetailsController extends Controller
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
     * ProductDetailsController constructor.
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

        $this->dummyPaymentLine = ['method' => 'cash', 'amount'    => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return'                         => 0, 'transaction_no' => ''];
    }

    public function callNumberGeneration($business_id, $location_id, $transaction_id = null, $user_id = null)
    {
        $call_No = '';
        /*         $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->where('user_id', $user_id)->first(); */
        $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->first();
        if (! empty($call_Number_Scheme)) {
            $start_number      = $call_Number_Scheme->start_number;
            $call_number_count = $call_Number_Scheme->call_number_count;
            if ($call_number_count === 999) {
                $call_No = $start_number;
            } else {
                $call_No = $call_number_count + $start_number;
            }
            //Increment the call no count
            CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => $call_No]);

        } else {
            $data = [
                ['business_id' => $business_id, 'location_id' => $location_id, 'user_id' => $user_id, 'call_number_count' => 1],
            ];
            CallNumberScheme::insert($data);
            $call_No = 1;
        }
        Transaction::where('id', $transaction_id)->update(['call_no' => $call_No]);
        return $call_No;
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
            //'is_enabled' => false,
            'print_type' => 'browser',
            'print'      => null,
            //'printer_config' => [],
            //'data' => []
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
        if (! empty($il->module_info['tables']) && ! empty($receipt_details) && $this->commonUtil->isModuleEnabled('table_module', $business_id)) {
            if (isset($il->module_info['tables']['show_table'])) {
                if (! empty($transaction->res_table_id)) {
                    $table = ResTable::find($transaction->res_table_id);
                }
                $receipt_details->table_name = ! empty($table->name) ? $table->name : null;
            }
        } else {
            $receipt_details->table_name = null;
        }

        /* Set Call code */
        $call_no = CallNumberScheme::callNumberGeneration($business_id, $location_id, $transaction_id, $user_id);

        $receipt_details->call_no        = $call_no;
        $receipt_details->transaction_id = $transaction_id ?? '';

        /* Set qr code */
        //$receipt_details->qrcode = "";

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

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getTables(Request $request)
    {
        if (! (auth()->user()->can('sell.pos.add'))) {
            return response(['errors' => 'Unauthorized action! Not alowed to add POS sell.'], 422);
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id     = $request->session()->get('user.id');
        $location_id = $request->get('location_id');
        if (! empty($location_id)) {
            $tables_enabled = false;
            $tables         = null;
            if ($this->commonUtil->isModuleEnabled('table_module', $business_id)) {
                $tables_enabled = true;
                $tables         = ResTable::where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->get();

            }
        } else {
            $tables_data    = [];
            $tables_enabled = $this->commonUtil->isModuleEnabled('table_module', $business_id) ? true : [];
        }

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id, $business_id);
        if (! empty($register)) {
            $created_date         = $register->created_at;
            $register->created_at = $register->created_at;
        } else {
            $created_date = Carbon::today()->toDateString() . ' 12:00:00';
        }
        $tables_data = [];
        if (! empty($tables)) {
            foreach ($tables as $key => $table) {
                $tables_data[$key]['id']          = $table->id ?? "";
                $tables_data[$key]['business_id'] = $table->business_id ?? "";
                $tables_data[$key]['location_id'] = $table->location_id ?? "";
                $tables_data[$key]['name']        = $table->name ?? "";
                $tables_data[$key]['created_by']  = $table->created_by ?? "";
                $tables_data[$key]['deleted_at']  = $table->deleted_at ?? "";
                $tables_data[$key]['created_at']  = $table->created_at ?? "";
                $tables_data[$key]['updated_at']  = $table->updated_at ?? "";

                $transactions = Transaction::where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('type', 'sell')
                    ->where('payment_status', 'due')
                    ->where('status', 'final')
                    ->where('is_suspend', 1)
                    ->where('created_at', '>=', $created_date)
                    ->get();
                //return response(['transactions' => $transactions], 200);
                $tables_data[$key]['transaction_id'] = null;
                $tables_data[$key]['invoice_no']     = null;
                $tables_data[$key]['res_table_id']   = null;
                $tables_data[$key]['res_waiter_id']  = null;

                if (! empty($transactions)) {
                    foreach ($transactions as $key2 => $transaction) {
                        if ($transaction->res_table_id == $table->id) {
                            $tables_data[$key]['transaction_id'] = $transaction->id ?? "";
                            $tables_data[$key]['invoice_no']     = $transaction->invoice_no ?? "";
                            $tables_data[$key]['res_table_id']   = $transaction->res_table_id ?? "";
                            $tables_data[$key]['res_waiter_id']  = $transaction->res_waiter_id ?? "";
                        }

                    }
                }
            }
        }
        return response(['tables' => $tables_data, 'tables_enabled' => $tables_enabled], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getServiceStaffs(Request $request)
    {
        if (! (auth()->user()->can('sell.pos.add'))) {
            return response(['errors' => 'Unauthorized action! Not alowed to add POS sell.'], 422);
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');
        if (! empty($location_id)) {
            //            $transaction_id = $request->get('transaction_id', null);
            //            if (!empty($transaction_id)) {
            //                $transaction = Transaction::find($transaction_id);
            //                $view_data = ['res_table_id' => $transaction->res_table_id,
            //                              'res_waiter_id' => $transaction->res_waiter_id,
            //                ];
            //            } else {
            //                $view_data = ['res_table_id' => null, 'res_waiter_id' => null];
            //            }

            $waiters_enabled = false;
            $waiters         = null;
            if ($this->commonUtil->isModuleEnabled('service_staff_module', $business_id)) {
                $waiters_enabled = true;

                $waiters = [];
                //Get all service staff roles
                $service_staff_roles = Role::where('business_id', $business_id)
                    ->where('is_service_staff', 1)
                    ->pluck('name')
                    ->toArray();

                //Get all users of service staff roles
                if (! empty($service_staff_roles)) {
                    $waiters = User::where('business_id', $business_id)
                        ->role($service_staff_roles);

                    if (! empty($location_id)) {
                        $waiters->permission(['location.' . $location_id, 'access_all_locations']);
                    }

                    $waiters = $waiters->select('id', DB::raw('CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")) as full_name'))->get();
                }
            }
        } else {
            $waiters         = [];
            $waiters_enabled = $this->commonUtil->isModuleEnabled('service_staff_module', $business_id) ? true : [];
        }

        $pos_settings = json_decode($request->session()->get('business.pos_settings'), true);

        $is_service_staff_required = (! empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : [];

        return response(['service_staff' => $waiters, 'service_staff_enabled' => $waiters_enabled, 'is_service_staff_required' => $is_service_staff_required], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getTypesOfServices(Request $request)
    {
        $business_id      = $request->session()->get('user.business_id');
        $types_of_service = [];
        if ($this->commonUtil->isModuleEnabled('direct_sales_module', $business_id)) {
            $types_of_service = TypesOfService::where('business_id', $business_id)->get();
        }

        return response(['types_of_service' => $types_of_service], 200);
    }

    /**
     * @param  Request  $request
     *
     * @throws \Throwable
     *
     * @return Response
     */
    public function getTypesOfServiceDetails(Request $request)
    {
        $location_id         = $request->input('location_id');
        $types_of_service_id = $request->input('types_of_service_id');

        $business_id = $request->session()->get('user.business_id');

        $types_of_service = TypesOfService::where('business_id', $business_id)
            ->where('id', $types_of_service_id)
            ->first();

        $price_group_id = ! empty($types_of_service->location_price_group[$location_id])
        ? $types_of_service->location_price_group[$location_id] : '';
        $price_group_name = '';

        if (! empty($price_group_id)) {
            $price_group      = SellingPriceGroup::find($price_group_id);
            $price_group_name = $price_group->name;
        }

        return response([
            'types_of_service'    => $types_of_service,
            'price_group_id'      => $price_group_id,
            'packing_charge'      => $types_of_service->packing_charge,
            'packing_charge_type' => $types_of_service->packing_charge_type,
            'price_group_name'    => $price_group_name,
        ], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function storeCustomer(Request $request)
    {
        if (! auth()->user()->can('customer.create')) {
            return response(['errors' => 'Unauthorized action! Not alowed to add customer.'], 422);
        }
        if (! auth()->user()->can('customer.view_own')) {
            return response(['errors' => 'Unauthorized action! Not alowed to view own customer.'], 422);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id     = $request->session()->get('user.id');

            $input = $request->only(['name', 'mobile']);

            if (! empty($input['dob'])) {
                $input['dob'] = $this->commonUtil->uf_date($input['dob']);
            }

            $input['business_id'] = $business_id;
            $input['created_by']  = $request->session()->get('user.id');

            $input['credit_limit']    = $request->input('credit_limit') != '' ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;
            $input['type']            = 'customer';
            $input['contact_status']  = 'active';
            $input['opening_balance'] = $this->commonUtil->num_uf($request->input('opening_balance'));

            $output = $this->contactUtil->createNewContact($input);

            if (is_object($output['data'])) {
                activity()
                    ->performedOn($output['data'])
                    ->useLog('customers')
                    ->log('customers added. Customer ID:' . $output['data']->id . ' & business ID:' . $business_id);
            }

        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg'                => __("messages.something_went_wrong"),
            ];
        }

        return response(['data' => $output], 200);
    }

    public function searchCustomer(Request $request)
    {
        $term = request()->input('query', '');

        $business_id = request()->session()->get('user.business_id');
        $user_id     = request()->session()->get('user.id');

        $contacts = Contact::where('business_id', $business_id)->active();

        $selected_contacts = User::isSelectedContacts($user_id);

        if ($selected_contacts) {
            $contacts->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
                ->where('uca.user_id', $user_id);
        }

        if (! empty($term)) {
            $contacts->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('supplier_business_name', 'like', '%' . $term . '%')
                    ->orWhere('mobile', 'like', '%' . $term . '%')
                    ->orWhere('contacts.contact_id', 'like', '%' . $term . '%');
            });
        }

        $contacts->select(
            'contacts.id',
            DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')' , ' (', contacts.mobile, ')')) AS text"),
            'mobile',
            'address_line_1',
            'city',
            'state',
            'pay_term_number',
            'pay_term_type',
            'balance',
            'supplier_business_name'
        )
            ->onlyCustomers();

        if (request()->session()->get('business.enable_rp') == 1) {
            $contacts->addSelect('total_rp');
        }
        $contactData = $contacts->get();
        $sql         = $contacts->toSql();
        $bindings    = $contacts->getBindings();
        return response(['success' => 1, 'bindings' => $bindings, 'sql' => $sql, 'contacts' => $contactData]);

        //return response(['contacts' => $contacts]);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function suspendedSellNote(Request $request)
    {
        // $input = $request->all();
        //        $business_id = session()->get('user.business_id');
        //        $user_id = $request->session()->get('user.id');
        //
        //        $discount = ['discount_type' => $input['discount_type'],
        //                     'discount_amount' => $input['discount_amount']
        //        ];
        //
        //        // transaction_date
        //        if (empty($request->input('transaction_date'))) {
        //            $input['transaction_date'] =  Carbon::now();
        //        } else {
        //            $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
        //        }
        //
        //        //Set commission agent
        //        $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
        //        $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
        //        if ($commsn_agnt_setting == 'logged_in_user') {
        //            $input['commission_agent'] = $user_id;
        //        }
        //
        //        $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);
        //
        //        $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
        //
        //        $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id']);
        //
        //        return response(['msg' => 'Sale added successfully'], 200);

        if (! auth()->user()->can('sell.pos.add')) {
            return response(['errors' => 'Unauthorized action! Not alowed add POS sell.'], 403);
        }

        $is_direct_sale = false;
        if (! empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
            return response()->json(['success' => 0, 'msg' => __('cash_register.register_closed'), 'url' => 'CashRegisterController@create'], 403);
            return response(['url' => 'CashRegisterController@create'], 403);
        }
        $business_id = $request->session()->get('user.business_id');
        $user_id     = $request->session()->get('user.id');

        try {
            $input = $request->except('_token');

            // Contact Id
            $contact_id = $request->get('contact_id', null);
            if ($contact_id == 'no_customer_select') {
                $contact_id = Contact::where('business_id', $business_id)->first()->id;
            }
            $input['contact_id'] = $contact_id;
            $enabled_modules     = $this->commonUtil->allModulesEnabled($business_id);

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            /* if ($is_credit_limit_exeeded !== false) {
            $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
            $output = ['success' => 0,
            'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
            'receipt' => null,
            ];
            if (!$is_direct_sale) {
            return $output;
            } else {
            return response(['url' => 'SellController@index', 'status' => $output], 403);
            }
            } */

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status']       = 'draft';
                $input['is_quotation'] = 1;
            }

            if (! empty($input['products'])) {

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount'            => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                //Set commission agent
                $input['commission_agent'] = ! empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting       = $request->session()->get('business.sales_cmsn_agnt');
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

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

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
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                }

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    /* foreach ($input['products'] as $product) {
                    $decrease_qty = $this->productUtil
                    ->num_uf($product['quantity']);
                    if (!empty($product['base_unit_multiplier'])) {
                    $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                    }

                    if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                    $product['product_id'],
                    $product['variation_id'],
                    $input['location_id'],
                    $decrease_qty
                    );
                    }

                    if ($product['product_type'] == 'combo') {
                    $this->productUtil->decreaseProductQuantityCombo(
                    $product['combo'],
                    $input['location_id']
                    );
                    }
                    } */

                    //Add payments to Cash Register
                    if (! $is_direct_sale && ! $transaction->is_suspend && ! empty($input['payment']) && ! $is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'], $input['clientCompany']);
                    }

                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = ! empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

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
                    ->performedOn($transaction)
                    ->useLog('transactions')
                    ->log('On Hold sales added. Transaction ID:' . $transaction->id);

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($transaction->id, $business_id);
                    return response(['url' => $url . '?print_on_load=true'], 200);
                }

                $msg     = trans("sale.pos_hold_order_added");
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
                    $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true);

                    /* Set qr code empty On Hold Sales */
                    if ($receipt['print']->qrcode) {
                        $receipt['print']->qrcode = "";
                    }

                }

                $output = ['success' => 1, 'msg' => $msg, 'print_invoice' => $print_invoice, 'receipt' => $receipt];
            } else {
                $output = ['success' => 0,
                    'msg'                => trans("messages.something_went_wrong"),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);

            $msg = $e->getMessage();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg'                => $msg,
            ];
        }

        if (! $is_direct_sale) {
            return response($output);
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return response(['status' => $output, 'url' => 'SellController@getQuotations']);
                } else {
                    return response(['status' => $output, 'url' => 'SellController@getDrafts']);
                }
            } elseif ($input['status'] == 'quotation') {
                return response(['status' => $output, 'url' => 'SellController@getQuotations']);
            } else {
                if (! empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return response(['status' => $output, 'url' => $redirect_url]);
                }
                return response(['status' => $output, 'url' => 'SellController@index']);
            }
        }
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getRecentTransactions(Request $request)
    {

        if (! auth()->user()->can('sell.pos.add')) {
            return response(['errors' => 'Unauthorized action! Not alowed to add POS sell.'], 422);
        }
        if (! auth()->user()->can('sell.pos.view')) {
            return response(['errors' => 'Unauthorized action! Not alowed to view POS sell.'], 422);
        }

        $business_id        = $request->session()->get('user.business_id');
        $user_id            = $request->session()->get('user.id');
        $transaction_status = $request->get('status');
        $transaction_id     = $request->get('transaction_id') ?? "";

        $location_id = $request->session()->get('user');

        /*  ->whereBetween('transactions.created_at', [$dateFrom, $dateTo]) */

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id, $business_id);
        if (! empty($register)) {
            $created_date = $register->created_at;
            //$created_date = Carbon::parse($register->created_at)->format('Y-m-d g:i:s');
            /* $register->created_at = $register->created_at; */
        } else {
            $created_date = Carbon::today()->toDateString() . ' 12:00:00';
        }

        /*  $created_date = "2023-11-06 00:00:00"; */
        $dateFrom = Carbon::yesterday()->toDateString() . ' 12:00:00';
        $dateTo   = Carbon::today()->toDateString() . ' 11:59:00';
        $query    = Transaction::where('business_id', $business_id)
            ->where('transactions.created_by', $user_id)
            ->where('transactions.created_at', '>=', $created_date)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0);

        if ($transaction_status == 'quotation') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 1);
        } elseif ($transaction_status == 'draft') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 0);
        } else {
            $query->where('transactions.status', $transaction_status);
        }
        $query->whereIn('transactions.payment_status', ['paid', 'returned', 'partial']);

        $transaction_sub_type = $request->get('transaction_sub_type');
        if (! empty($transaction_sub_type)) {
            $query->where('transactions.sub_type', $transaction_sub_type);
        } else {
            $query->where('transactions.sub_type', null);
        }

        if (! empty($transaction_id)) {
            $query->where('transactions.id', $transaction_id);
        }

        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
        /*  ->select('transactions.*') */
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.final_total',
                'transactions.location_id',
                'transactions.call_no',
                'transactions.created_at',
                'transactions.payment_status',
                'transactions.tip',
                'transactions.delivery_cost',
                'transactions.service_charge',
                'transactions.bag_fee',
                'transactions.deliverect_order_id',
                'transactions.transaction_date',
                'transactions.discount_type',
                'transactions.discount_amount'
            )
            ->with(['contact', 'table', 'location'])
            ->limit(300)
            ->get();

        $printer_type     = 'browser';
        $business_details = $this->businessUtil->getDetails($business_id);
        $sql              = $query->toSql();
        $bindings         = $query->getBindings();

        $print = [];
        foreach ($transactions as $transaction) {
            $location_details  = BusinessLocation::find($transaction->location_id);
            $invoice_layout_id = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
            $invoice_layout    = $this->businessUtil->invoiceLayout($business_id, $transaction->location_id, $invoice_layout_id);

            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $transaction['print']                        = $this->transactionUtil->getReceiptDetails($transaction->id, $transaction->location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $business_id);
            $transaction['print']->call_no               = $transaction->call_no;
            $transaction['print']->generatedBase64String = $transaction->generatedBase64String;
            $transaction['print']->created_at            = Carbon::parse($transaction->created_at)->format('Y-m-d H:i A');

        }

        $response = response(['transactions' => $transactions, 'location_id' => $location_id, 'transaction_sub_type' => $transaction_sub_type, 'query' => $sql, 'bindings' => $bindings, 'register' => $register], 200);

        // $transactions = $ProductDetails_Controller->printhtml($transactions);

        return $response;
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function recentTransactionDelete(Request $request)
    {
        if (! auth()->user()->can('sell.pos.return')) {
            return response(['errors' => 'Unauthorized action! Not alowed to return POS sell.'], 403);
        }
        try {
            $transaction_id                               = $request->get('transactionID');
            $return_reason                                = $request->get('return_reason');
            $return_payment_method                        = $request->get('return_payment_method');
            $card_transaction_id                          = $request->get('card_transaction_id');
            $return_reason_other                          = $request->get('return_reason_other');
            $return_reasom_array                          = [];
            $return_reasom_array['return_payment_method'] = $return_payment_method;
            $return_reasom_array['card_transaction_id']   = $card_transaction_id;
            if ($return_reason == 'other') {
                $return_reasom_array['return_reason'] = $return_reason_other ?? "";
            } else {
                $return_reasom_array['return_reason'] = $return_reason;
            }
            $business_id = request()->session()->get('user.business_id');
            if (! empty($transaction_id) && ! empty($business_id)) {
                //Check if the transaction can be edited or not.
                /* $edit_days = request()->session()->get('business.transaction_edit_days');
                if (!$this->transactionUtil->canBeEdited($transaction_id, $edit_days)) {
                return response(['success' => 0, 'msg' => __('messages.transaction_deleted_not_allowed', ['days' => $edit_days])], 422);
                } */
                //Begin transaction
                DB::beginTransaction();
                $sell_details = TransactionSellLine::with([
                    'product', 'product.variations', 'product.modifier_sets.variations', 'variations', 'modifiers',
                ])->where('transaction_id', $transaction_id)->where('parent_sell_line_id', '=', null)->get();
                $transaction = Transaction::where('id', $transaction_id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines', 'payment_lines'])
                    ->first();
                $location_id = $transaction->location_id;
                if (! empty($sell_details)) {
                    foreach ($sell_details as $lineObj) {
                        $enable_stock = $lineObj->product->enable_stock;
                        if (! empty($lineObj->id) && ! empty($lineObj->quantity)) {
                            TransactionSellLine::where('id', $lineObj->id)->update(['quantity_returned' => $lineObj->quantity]);
                            $back_inventory_qty = ($lineObj->quantity);
                            if ($enable_stock) {
                                $this->transactionUtil->increaseQuantity($location_id, $lineObj->product_id, $lineObj->variation_id, $back_inventory_qty);
                            } else {
                                $recipe = MfgRecipe::where('variation_id', $lineObj->variation_id)
                                    ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                        $query->orderBy('sort_order', 'asc');
                                    }])->first();
                                if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($back_inventory_qty)) {
                                    $this->IncreaseInventory($recipe, $business_id, $location_id, $back_inventory_qty);
                                }
                                if (! empty($lineObj->modifiers)) {
                                    foreach ($lineObj->modifiers as $key => $modifierObj) {
                                        if (! empty($modifierObj->id) && ! empty($modifierObj->quantity)) {
                                            TransactionSellLine::where('id', $modifierObj->id)->update(['quantity_returned' => $modifierObj->quantity]);
                                            $recipeModifier = MfgRecipe::where('variation_id', $modifierObj->variation_id)
                                                ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                                    $query->orderBy('sort_order', 'asc');
                                                }])->first();
                                            $back_modifiers_inventory_qty = ($modifierObj->quantity);
                                            if (! empty($recipeModifier) && ! empty($business_id) && ! empty($location_id) && ! empty($back_modifiers_inventory_qty)) {
                                                $this->IncreaseInventory($recipeModifier, $business_id, $location_id, $back_modifiers_inventory_qty);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                //$output = $this->transactionUtil->deleteSale($business_id, $transaction_id, $return_reasom_array);
                $output = $this->transactionUtil->returnSale($business_id, $transaction_id, $return_reasom_array);
                if (is_object($output['data'])) {
                    activity()
                        ->performedOn($output['data'])
                        ->useLog('transactions')
                        ->log('Transaction returned.Transaction ID:' . $output['data']->id . ' & business ID:' . $business_id);
                }
                DB::commit();

            } else {
                return response(['success' => 0, 'msg' => __('sale.no_transaction_id')], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output['success'] = false;
            $output['msg']     = $e->getMessage();
        }
        return response($output);
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function productDraft(Request $request)
    {
        $input       = $request->all();
        $business_id = $request->session()->get('user.business_id');
        $user_id     = $request->session()->get('user.id');

        $discount = ['discount_type' => $input['discount_type'],
            'discount_amount'            => $input['discount_amount'],
        ];
        $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

        $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

        $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

        $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], false, null, [], true, $enabled_modules, $business_id);

        return response(['success' => 'Draft Successfully'], 200);
    }

    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|Response
     */
    public function finalizePayment(Request $request)
    {
        if (! auth()->user()->can('sell.pos.add')) {
            return response(['errors' => 'Unauthorized action! Not alowed to add POS sell.'], 403);
        }

        $is_direct_sale = false;
        if (! empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {

            return response()->json(['success' => 0, 'msg' => __('cash_register.register_closed'), 'url' => 'CashRegisterController@create'], 403);

            //return response(['output' => $output, 'url' => 'CashRegisterController@create'], 403);
        }
        $user_id     = $request->session()->get('user.id');
        $business_id = $request->session()->get('user.business_id');

        try {
            $input = $request->except('_token');

            // Contact Id
            $contact_id = $request->get('contact_id', null);
            if ($contact_id == 'no_customer_select') {
                $contact_id = Contact::where('business_id', $business_id)->first()->id;
            }
            $input['contact_id'] = $contact_id;
            $location_id         = $input['location_id'] ?? "";
            $enabled_modules     = $this->commonUtil->allModulesEnabled($business_id);

            //Check Customer credit limit
            /*  $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            if ($is_credit_limit_exeeded !== false) {
            $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
            $output = ['success' => 0,
            'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
            'receipt' => null,
            ];
            if (!$is_direct_sale) {
            return $output;
            } else {
            return response(['url' => 'SellController@index', 'status' => $output], 403);
            }
            } */

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if (isset($input['status']) && ($input['status'] == 'quotation')) {
                $input['status']       = 'draft';
                $input['is_quotation'] = 1;
            }

            // return response(['products' => $input['products'], 'url' => 'SellController@index']);

            if (! empty($input['products'])) {

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount'            => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                //Set commission agent
                $input['commission_agent'] = ! empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting       = $request->session()->get('business.sales_cmsn_agnt');
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
                $input['clientCompany'] = request()->get('clientCompany') ?? "";
                $input['order_type_Id'] = request()->get('order_type_Id') ?? "";

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
                $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], false, null, [], true, $enabled_modules, $business_id);

                if (! $is_direct_sale) {
                    //Add change return
                    $change_return              = $this->dummyPaymentLine;
                    $change_return['amount']    = $input['change_return'];
                    $change_return['is_return'] = 1;
                    $input['payment'][]         = $change_return;
                }

                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                if (! $transaction->is_suspend && ! empty($input['payment']) && ! $is_credit_sale) {
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                }

                //Check for final and do some processing.
                if (isset($input['status']) && ($input['status'] == 'final')) {
                    foreach ($input['products'] as $product) {
                        $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                        if (! empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }
                        if ($product['enable_stock']) {
                            // return response(['product_id' => $product['product_id'], 'variation_id' => $product['variation_id'], 'decrease_qty' => $decrease_qty, 'location_id' => $location_id]);

                            $res = $this->productUtil->decreaseProductQuantity($product['product_id'], $product['variation_id'], $location_id, $decrease_qty);
                            //return response(['$res' => $res]);

                        } else {
                            $recipe = MfgRecipe::where('variation_id', $product['variation_id'])
                                ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit',
                                    'ingredients' => function ($query) {
                                        $query->orderBy('sort_order', 'asc');
                                    }])
                                ->first();
                            //return response(['recipe' => $recipe]);

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

                    //Add payments to Cash Register
                    if (! $is_direct_sale && ! $transaction->is_suspend && ! empty($input['payment']) && ! $is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'], $input['clientCompany']);
                    }

                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = ! empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

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
                    ->performedOn($transaction)
                    ->useLog('transactions')
                    ->log('Sales added. Transaction ID:' . $transaction->id);

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($transaction->id, $business_id);
                    return response(['url' => $url . '?print_on_load=true'], 200);
                }

                $msg               = trans("sale.pos_sale_added");
                $receipt           = '';
                $invoice_layout_id = $request->input('invoice_layout_id');
                $print_invoice     = false;
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

                if ($print_invoice) {
                    $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id, $user_id);
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
            //$msg = trans("messages.something_went_wrong");
            $msg = $e->getMessage();
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
        }

        if (! $is_direct_sale) {
            return response($output);
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return response(['status' => $output, 'url' => 'SellController@getQuotations']);
                } else {
                    return response(['status' => $output, 'url' => 'SellController@getDrafts']);
                }
            } elseif ($input['status'] == 'quotation') {
                return response(['status' => $output, 'url' => 'SellController@getQuotations']);
            } else {
                if (! empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return response(['status' => $output, 'url' => $redirect_url]);
                }
                return response(['status' => $output, 'url' => 'SellController@index']);
            }
        }
    }
    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|Response
     */
    public function IncreaseInventory($recipe, $business_id, $location_id, $product_quantity)
    {
        try {

            if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($product_quantity)) {
                $ingredients = $this->mfgUtil->getIngredientDetails($recipe, $business_id, $location_id);
                //return response()->json(['success' => 1, 'ingredients' => $ingredients], 200);
                foreach ($ingredients as $ingredient) {
                    if ($ingredient['enable_stock']) {
                        $ingredient_quantity = $this->mfgUtil->calc_percentage($ingredient['quantity'], $ingredient['waste_percent'], $ingredient['quantity']);
                        $increase_qty        = $ingredient_quantity * $product_quantity;
                        $this->transactionUtil->increaseQuantity($location_id, $ingredient['product_id'], $ingredient['variation_id'], $increase_qty);
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
     * @return array|Application|ResponseFactory|Response
     */
    public function recentTransactionPrint(Request $request)
    {
        $transaction_id = $request->get('transaction_id');
        try {
            $output = ['success' => 0,
                'msg'                => trans("messages.something_went_wrong"),
            ];

            $business_id = $request->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                ->where('id', $transaction_id)
                ->with(['location'])
                ->first();

            if (empty($transaction)) {
                return $output;
            }

            $printer_type = 'browser';
            if (! empty(request()->input('check_location')) && request()->input('check_location') == true) {
                $printer_type = $transaction->location->receipt_printer_type;
            }
            $business_details = $this->businessUtil->getDetails($business_id);
            $location_details = BusinessLocation::find($transaction->location_id);

            $invoice_layout_id = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
            $invoice_layout    = $this->businessUtil->invoiceLayout($business_id, $transaction->location_id, $invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $receipt = $this->transactionUtil->getReceiptDetails($transaction_id, $transaction->location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $business_id);

            if (! empty($receipt)) {
                $output = ['success' => 1, 'receipt' => $receipt];
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg'                => trans("messages.something_went_wrong"),
            ];
        }

        return response($output);
    }

    private function __getwarranties()
    {
        $business_id         = session()->get('user.business_id');
        $common_settings     = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties          = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];

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
        if ((strlen($scale_setting['label_prefix']) == 0) || Str::startsWith($scale_barcode, $scale_setting['label_prefix'])) {
            $scale_barcode = substr($scale_barcode, strlen($scale_setting['label_prefix']));

            //Get product sku, trim left side 0
            $sku = ltrim(substr($scale_barcode, 0, $scale_setting['product_sku_length'] + 1), '0');

            //Get quantity integer
            $qty_int = substr($scale_barcode, $scale_setting['product_sku_length'] + 1, $scale_setting['qty_length'] + 1);

            //Get quantity decimal
            $qty_decimal = '0.' . substr($scale_barcode, $scale_setting['product_sku_length'] + $scale_setting['qty_length'] + 2, $scale_setting['qty_length_decimal'] + 1);

            $qty = (float) $qty_int + (float) $qty_decimal;

            //Find the variation id
            $result = $this->productUtil->filterProduct($business_id, $sku, null, false, null, [], ['sub_sku'], false, 'exact')->first();

            if (! empty($result)) {
                return ['variation_id' => $result->variation_id,
                    'qty'                  => $qty,
                    'success'              => true,
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
