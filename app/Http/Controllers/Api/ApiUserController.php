<?php
namespace App\Http\Controllers\Api;

use App;
use App\Business;
use App\BusinessLocation;
use App\CallNumberScheme;
use App\CashRegister;
use App\ClientCompany;
use App\Contact;
use App\Http\Controllers\Controller;
use App\InvoiceLayout;
use App\Media;
use App\OrderTypes;
use App\Product;
use App\Transaction;
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
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Utils\ManufacturingUtil;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class ApiUserController extends Controller
{

    /**
     * All Utils instance.
     */
    protected $contactUtil;
    protected $cashRegisterUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $commonUtil;
    protected $mfgUtil;
    /** @var array<string, string> */
    protected array $additionalOptions = [];

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

    public function openRegisterAppUser(Request $request)
    {
        try {
            $input         = $request->all();
            $user_id       = $input['user_id'] ?? "";
            $business_id   = $input['business_id'] ?? "";
            $location_id   = $input['location_id'] ?? "";
            $user          = User::where('id', $user_id)->with(['media'])->first();
            $user_language = $user->language ?? 'ar';
            App::setLocale($user_language);

            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.add')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed add POS sell.'], 403);
                }
                $initial_amount = 0;
                if (! empty($request->input('amount'))) {
                    $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
                }
                //$call_register = CashRegister::where('business_id', $business_id)->where('user_id', $user_id)->where('location_id', $location_id)->where('status', 'open')->first();
                $call_register = CashRegister::where('business_id', $business_id)->where('location_id', $location_id)->where('status', 'open')->first();
                if ($call_register) {
                    $register_user_id = $call_register->user_id ?? null;
                    $status           = $call_register->status ?? null;
                    if (($register_user_id != $user_id) && ($status = 'open')) {
                        return response()->json(['success' => 0, 'errors' => 'Unauthorized action! You Cannot open same register. Register Already open by another user..'], 403);
                    }
                    return response()->json(['success' => 1, 'msg' => 'Register Already opened', 'call_register' => $call_register], 200);
                } else {

                    if (! empty($call_register)) {
                        $register_user_id = $call_register->user_id ?? null;
                        $status           = $call_register->status ?? null;
                        if (($register_user_id != $user_id) && ($status = 'open')) {
                            return response()->json(['success' => 0, 'errors' => 'Unauthorized action! You Cannot open same register. Register Already open by another user..'], 403);
                        }

                        $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->first();
                        if (! empty($call_Number_Scheme)) {
                            //CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => 0]);
                            CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => $call_Number_Scheme->call_number_count]);
                        } else {
                            $data = [['business_id' => $business_id, 'location_id' => $location_id, 'user_id' => $user_id, 'call_number_count' => 0]];
                            CallNumberScheme::insert($data);
                        }
                    } else {
                        $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->first();
                        if (! empty($call_Number_Scheme)) {
                            CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => 0]);
                        } else {
                            $data = [['business_id' => $business_id, 'location_id' => $request->input('location_id'), 'user_id' => $user_id, 'call_number_count' => 0]];
                            CallNumberScheme::insert($data);
                        }
                    }

                    $register = CashRegister::create([
                        'business_id' => $business_id,
                        'user_id'     => $user_id,
                        'status'      => 'open',
                        'location_id' => $request->input('location_id'),
                        'created_at'  => \Carbon::now()->format('Y-m-d H:i:00'),
                    ]);

                    if (! empty($initial_amount)) {
                        $register->cash_register_transactions()->create([
                            'amount'           => $initial_amount,
                            'pay_method'       => 'cash',
                            'type'             => 'credit',
                            'transaction_type' => 'initial',
                        ]);
                    }
                    return response()->json(['success' => 1, 'msg' => 'Register Opened Now', 'register' => $register], 200);
                }
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }
    public function createCashRegister(Request $request)
    {
        try {
            $input         = $request->all();
            $sub_type      = $input['sub_type'] ?? "";
            $user_id       = $input['user_id'] ?? "";
            $business_id   = $input['business_id'] ?? "";
            $location_id   = $input['location_id'] ?? "";
            $user          = User::where('id', $user_id)->with(['media'])->first();
            $user_language = $user->language ?? 'ar';
            App::setLocale($user_language);

            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.add')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed add POS sell.'], 403);
                }
                $register_details = CashRegister::where('cash_registers.business_id', $business_id)
                    ->where('cash_registers.user_id', $user_id)
                    ->where('cash_registers.location_id', $location_id)
                    ->where('cash_registers.status', 'open')
                    ->first();
                if ($register_details) {
                    return response()->json(['success' => 1, 'msg' => 'Register opened', 'register_details' => $register_details], 200);
                } else {
                    return response()->json(['success' => 0, 'msg' => 'Register not opened yet'], 200);

                }
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => $e->getMessage()], 500);
        }
    }

    public function getProfile(Request $request)
    {

        try {
            $input       = $request->all();
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $user        = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.add')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to view POS sell.'], 403);
                }
                $accessToken                = $user->tokens ?? "";
                $business_locations         = BusinessLocation::forDropdownApi($business_id, $user);
                $user['business_locations'] = $business_locations ?? "";
                foreach ($user->tokens as $token) {
                    $user['tokens']    = $token->id ?? "";
                    $user['client_id'] = $token->client_id ?? "";
                    $user['tokenID']   = $token->id ?? "";
                }
                return response()->json(['success' => 1, 'user' => $user], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    public function getInvoiceLayout(Request $request)
    {
        try {
            $input = $request->all();

            $user_id           = $input['user_id'] ?? "";
            $business_id       = $input['business_id'] ?? "";
            $location_id       = $input['location_id'] ?? "";
            $invoice_layout_id = $input['invoice_layout_id'] ?? "";
            $user              = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.add')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to add POS sell'], 403);
                }

                $accessToken       = $user->tokens ?? "";
                $invoiceLayouarray = [];
                $invoice_layout    = InvoiceLayout::findOrFail($invoice_layout_id);
                if (($invoice_layout)) {
                    $invoice_layout->module_info              = json_decode($invoice_layout->module_info, true);
                    $invoice_layout->table_tax_headings       = ! empty($invoice_layout->table_tax_headings) ? json_decode($invoice_layout->table_tax_headings) : ['', '', '', ''];
                    $invoice_logos_url                        = config('app.invoice_logos_url');
                    $invoice_layout['logo_url']               = $invoice_layout->show_logo == 1 && ! empty($invoice_layout->logo) ? asset('uploads/invoice_logos/' . $invoice_layout->logo) : false;
                    $invoiceLayoutDesign                      = $this->getDesigns();
                    $invoiceLayouarray['invoice_layout']      = $invoice_layout ?? "";
                    $invoiceLayouarray['invoiceLayoutDesign'] = $invoiceLayoutDesign ?? "";
                    return response()->json(['success' => 1, ' invoiceLayout' => $invoiceLayouarray], 200);
                }
                return response()->json(['success' => 1, ' invoiceLayout' => $invoiceLayoutDesign], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);

        }
    }

    private function getDesigns()
    {
        return ['classic' => __('lang_v1.classic') . ' (' . __('lang_v1.for_normal_printer') . ')',
            'elegant'         => __('lang_v1.elegant') . ' (' . __('lang_v1.for_normal_printer') . ')',
            'detailed'        => __('lang_v1.detailed') . ' (' . __('lang_v1.for_normal_printer') . ')',
            'columnize-taxes' => __('lang_v1.columnize_taxes') . 'Columnize Taxes (' . __('lang_v1.for_normal_printer') . ')',
            'slim'            => __('lang_v1.slim') . ' (' . __('lang_v1.recomended_for_80mm') . ')',
            'slim2'           => __('lang_v1.slim') . ' 2 (' . __('lang_v1.recomended_for_58mm') . ')',
        ];
    }

    public function finalizePaymentApi(Request $request)
    {
        try {
            /* $input = $request->all();
            return response()->json(['success' => 1, 'user_id' => $input], 200); */

            $input       = $request->all();
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $location_id = $input['location_id'] ?? "";
            $tax_rate_id = $input['tax_rate_id'] ?? 1;

            //return response()->json(['success' => 1, 'user_id' => $user_id], 200);
            //\Log::emergency("tax_id:" . $input['products']['tax_id']);
            \Log::emergency("calling RecentTransaction Functions from finalizePaymentApi UserID: " .$user_id . " business_id: " . $business_id );

            $user          = User::where('id', $user_id)->with(['media'])->first();
            $user_language = $user->language ?? 'ar';
            App::setLocale($user_language);
            //return response()->json(['user_id' => $user_id, 'user' => $user], 200);

            if (! (($user) && ($user->tokens) && ((count($user->tokens) > 0)))) {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }

            if (! $user->can('sell.pos.add')) {
                return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to add POS sell.'], 403);
            }

            $selectedBusiness = Business::where('id', $business_id)->first();
            //$enabled_modules = $selectedBusiness->enabled_modules ?? "";
            $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

            $is_direct_sale = false;
            if (! empty($request->input('is_direct_sale'))) {
                $is_direct_sale = true;
            }

            //Check if there is a open register, if no then redirect to Create Register screen.
            if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister($user_id) == 0) {
                return response()->json(['success' => 0, 'msg' => __('cash_register.register_not_opened')], 500);
            }

            $input = $request->except('_token') ?? "";

            // Contact Id
            $contact_id = $request->get('contact_id', null);
            if ($contact_id == 'no_customer_select') {
                $contact_id = Contact::where('business_id', $business_id)->first()->id;
            }
            $input['contact_id'] = $contact_id;

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
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $tax_rate_id, $discount);
                //return response()->json(['success' => 1, ' invoice_total' => $invoice_total], 200);

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
                $commsn_agnt_setting       = $request->input('is_cmmsn_agnt');
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
                $input['clientCompany']       = request()->get('clientCompany') ?? "";
                $input['order_type_Id']       = request()->get('order_type_Id') ?? "";
                $input['is_created_from_api'] = 1;

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
                $res         = $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], false, null, [], true, $enabled_modules, $business_id);

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
                    //update product stock
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
                                //$output = ['success' => 1, 'recipe' => $recipe, 'business_id' => $business_id, 'location_id' => $location_id, 'product' => $product];
                                // return response($output);
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
                    $business_details = $this->businessUtil->getDetails($business_id);

                    if ($business_details->enable_rp == 1) {
                        $redeemed = ! empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table

                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = ['id'   => $business_id,
                        'accounting_method' => $request->input('accounting_method'),
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
                    ->log('Sales added by API. Transaction ID:' . $transaction->id);

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($transaction->id, $business_id);
                    return response()->json(['url' => $url . '?print_on_load=true'], 200);

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
                // return response()->json(['print_invoice' => $print_invoice, 200]);

                if ($print_invoice) {
                    $receipt = $this->receiptContentApi($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id, $user_id);
                }
                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];

            } else {
                $output = ['success' => 0,
                    'msg'                => trans("messages.something_went_wrong"),
                    'receipt'            => null,
                ];
            }
            return response()->json($output, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);

            $msg    = $e->getMessage();
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
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
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
        }

    }

    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|RedirectResponse|Response
     */
    public function updateFinalizePaymentApi(Request $request)
    {
        try {
            $input       = $request->all();
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $id          = $request->get('transactionId');
            $contact_id  = $input['contact_id'] ?? null;
            $location_id = $input['location_id'] ?? "";
            $tax_rate_id = $input['tax_rate_id'] ?? 1;
            \Log::emergency("calling RecentTransaction Functions from updateFinalizePaymentApi UserID: " .$user_id . " business_id: " . $business_id );

            $user = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.edit')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to edit POS sell.'], 403);
                }
                $selectedBusiness = Business::where('id', $business_id)->first();
                //$enabled_modules = $selectedBusiness->enabled_modules ?? "";
                $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

                $business_details = $this->businessUtil->getDetails($business_id);

                //status is send as quotation from edit sales screen.
                $input['is_quotation'] = 0;
                if ($input['status'] == 'quotation') {
                    $input['status']       = 'draft';
                    $input['is_quotation'] = 1;
                }

                $is_direct_sale = false;
                if (! empty($input['products'])) {
                    //Get transaction value before updating.
                    $transaction_before = Transaction::find($id);
                    $status_before      = $transaction_before->status ?? "";
                    $rp_earned_before   = $transaction_before->rp_earned ?? "";
                    $rp_redeemed_before = $transaction_before->rp_redeemed ?? "";
                    if ($transaction_before->is_direct_sale == 1) {
                        $is_direct_sale = true;
                    }

                    // Contact Id
                    if ($contact_id == 'no_customer_select') {
                        $contact_id = Contact::where('business_id', $business_id)->first()->id;
                    } else {
                        $contacts = Contact::where('business_id', $business_id)->where('id', $contact_id)->first();
                        if (! empty($contacts)) {
                            $contact_id = $contacts->id;
                        } else {
                            $contact_id = Contact::where('business_id', $business_id)->first()->id;
                        }

                    }
                    $input['contact_id'] = $contact_id;

                    //Check if there is a open register, if no then redirect to Create Register screen.
                    if (! $is_direct_sale && $this->cashRegisterUtil->countOpenedRegister($user_id) == 0) {
                        return response()->json(['success' => 0, 'msg' => __('cash_register.register_not_opened')], 500);
                    }

                    $commsn_agnt_setting = $request->input('is_cmmsn_agnt');

                    $discount = ['discount_type' => $input['discount_type'],
                        'discount_amount'            => $input['discount_amount'],
                    ];
                    $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $tax_rate_id, $discount);

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
                    //return response()->json(['success' => 1, 'products' => $input['location_id']], 200);

                    //Update Sell lines
                    $deleted_lines = $this->transactionUtil->createOrUpdateSellLinesTransaction($transaction, $input['products'], $input['location_id'], true, $status_before, [], true, $enabled_modules, $business_id);
                    //Update update lines
                    $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;
                    if (isset($input['status']) && ($input['status'] == 'final')) {
                        //update product stock

                        foreach ($input['products'] as $product) {
                            $decrease_qty = $this->productUtil->num_uf($product['quantity']);
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
                                $this->productUtil->decreaseProductQuantityCombo($product['combo'], $input['location_id']);
                            }
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
                            $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment'], $business_id, $user_id);
                            //Update cash register
                            $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment'], $user_id);
                        }

                    }

                    if ($business_details->enable_rp == 1) {
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
                        'accounting_method' => $business_details->accounting_method,
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
                        ->causedBy($user)
                        ->performedOn($transaction)
                        ->useLog('transactions')
                        ->withProperties(['Business_ID' => $business_id])
                        ->log('Sales updated by API. Transaction ID:' . $transaction->id);

                    DB::commit();

                    $msg     = '';
                    $receipt = '';

                    $invoice_layout_id = $request->input('invoice_layout_id');

                    if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                        $msg = trans("sale.draft_added");
                    } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                        $msg = trans("lang_v1.quotation_updated");
                        if (! $is_direct_sale) {
                            $receipt = $this->receiptContentApi($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id, $user_id);
                        } else {
                            $receipt = null;
                        }
                    } elseif ($input['status'] == 'final') {
                        $msg = trans("sale.pos_sale_updated");
                        if (! $is_direct_sale) {
                            $receipt = $this->receiptContentApi($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id, $user_id);
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
                return response()->json($output, 200);

            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);

            $msg    = $e->getMessage();
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
        }
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
    private function receiptContentApi($business_id, $location_id, $transaction_id, $printer_type = null, $is_package_slip = false, $from_pos_screen = true, $invoice_layout_id = null, $user_id = null)
    {
        $output = [
            'is_enabled' => true,
            'print_type' => null,
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
            $output['print_type'] = 'app';
            $output['print']      = $receipt_details;
        } elseif ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['print']      = $receipt_details;
        } elseif ($receipt_printer_type == 'browser') {
            $output['print_type'] = 'browser';
            $output['print']      = $receipt_details;
            //$output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
        } else {
            $output['print_type'] = 'else';
            //$layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';
            $output['print'] = $receipt_details;
        }

        return $output;
    }

    public function getApiLocations(Request $request)
    {
        try {
            $input   = $request->all();
            $user_id = $input['user_id'] ?? "";
            $user    = User::where('id', $user_id)->with(['media'])->first();
            if (! $user->can('sell.pos.view')) {
                return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to view POS sell.'], 403);
            }
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_id           = $input['business_id'] ?? "";
                $selectedBusiness      = Business::where('id', $business_id)->first();
                $custom_contact        = ClientCompany::where('business_id', $business_id)->get();
                $enable_rp             = $selectedBusiness->enable_rp ?? "";
                $transaction_edit_days = $selectedBusiness->transaction_edit_days ?? "";
                $business_name         = $selectedBusiness->name ?? "";
                $tax_number_1          = $selectedBusiness->tax_number_1 ?? "";
                $accounting_method     = $selectedBusiness->accounting_method ?? "";

                $selectedBusinessLocation = CashRegister::where('business_id', $business_id)->where('user_id', $user_id)
                    ->where('status', 'open')->where('closed_at', '=', null)->first();
                if (! empty($selectedBusinessLocation)) {
                    $order_types         = OrderTypes::get();
                    $location_id         = $selectedBusinessLocation->location_id ?? null;
                    $query               = BusinessLocation::where('id', $location_id)->Active();
                    $permitted_locations = $user->permitted_locations_api($business_id) ?? "";
                    if ($permitted_locations != 'all') {
                        $query->whereIn('id', $permitted_locations);
                    }

                    $query->select(
                        \DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
                        'id',
                        'business_id',
                        'invoice_layout_id',
                        'receipt_printer_type',
                        'number_of_thermal_print_copy',
                        'selling_price_group_id',
                        'default_payment_accounts'
                    );
                    $sql                = $query->toSql();
                    $bindings           = $query->getBindings();
                    $business_locations = $query->get();
                    if (! empty($business_locations)) {
                        foreach ($business_locations as $location) {
                            if (! empty($selectedBusinessLocation) && $selectedBusinessLocation->location_id == $location->id) {
                                $location['selected_business_location'] = 'selected';
                            } else {
                                $location['selected_business_location'] = null;
                            }
                            if (! empty($enable_rp)) {
                                $location['enable_rp'] = $enable_rp;
                            }
                            if (! empty($transaction_edit_days)) {
                                $location['transaction_edit_days'] = $transaction_edit_days;
                            }

                            if (! empty($accounting_method)) {
                                $location['accounting_method'] = $accounting_method;
                            }

                            if (! empty($tax_number_1)) {
                                $location['tax_number_1'] = $tax_number_1;
                            }

                            if (! empty($tax_number_1)) {
                                $location['business_name'] = $business_name;
                            }
                            if (! empty($custom_contact)) {
                                $location['custom_contact'] = $custom_contact;
                            }
                            if (! empty($order_types)) {
                                $location['order_types'] = $order_types;
                            }
                        }
                        return response()->json(['success' => 1, ' business_locations' => $business_locations, 'sql' => $sql, 'bindings' => $bindings], 200);
                    } else {
                        return response()->json(['success' => 0, ' business_locations' => null, 'sql' => $sql, 'bindings' => $bindings], 200);
                    }
                } else {
                    return response()->json(['success' => 0, ' business_locations' => null], 200);

                }
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);

        }

    }

    public function getInvoicePrint(Request $request)
    {
        try {
            $input = $request->all();
            // return response()->json(['success' => 1, '  input' => $input], 200);
            $user_id = $input['user_id'] ?? "";
            $user    = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $invoicePrint = [
                    "success" => 1,
                    "msg"     => "Sale added successfully",
                    "receipt" => [
                        "print_type" => "browser",
                        "print"      => [
                            "header_text"                  => "<p style=\"text-align: center;\">Welcome to Fastich POS</p>",
                            "business_name"                => "",
                            "location_name"                => "03 فرع أبحر",
                            "location_id"                  => 8,
                            "business_id"                  => 2,
                            "sub_heading_line1"            => "Shawerma Elak - شاورما الك",
                            "sub_heading_line2"            => "الرقم الضريبي 310099050100003",
                            "sub_heading_line3"            => "فاتورة ضريبية مبسطة",
                            "sub_heading_line4"            => "",
                            "sub_heading_line5"            => "Simplified Tax Invoice",
                            "table_product_label"          => "Product",
                            "table_qty_label"              => "QTY",
                            "table_unit_price_label"       => "Unit Price",
                            "table_subtotal_label"         => "Subtotal",
                            "kitchenExist"                 => true,
                            "kitchens"                     => [
                                [
                                    "kitchenNumber"   => "Obhur Kitchen",
                                    "kitchenCategory" => [
                                        [
                                            "kitchenCatID" => 23,
                                            "catName"      => "قسم الشاورما",
                                            "short_code"   => "cat-1",
                                        ],
                                        [
                                            "kitchenCatID" => 24,
                                            "catName"      => "قسم البرجر",
                                            "short_code"   => "cat-2",
                                        ],
                                        [
                                            "kitchenCatID" => 26,
                                            "catName"      => "الأصناف الجانبية",
                                            "short_code"   => "cat-4",
                                        ],
                                        [
                                            "kitchenCatID" => 27,
                                            "catName"      => "المشروبات",
                                            "short_code"   => "cat-5",
                                        ],
                                        [
                                            "kitchenCatID" => 30,
                                            "catName"      => "تطبيقات أخرى",
                                            "short_code"   => "cat-7",
                                        ],
                                        [
                                            "kitchenCatID" => 25,
                                            "catName"      => "أطباق منوعة",
                                            "short_code"   => "cat-8",
                                        ],
                                    ],
                                ],
                            ],
                            "kitchenStations"              => [
                                [
                                    "id"                   => 14,
                                    "business_id"          => 2,
                                    "business_location_id" => 8,
                                    "station_name"         => "Obhur Kitchen",
                                    "created_at"           => "2024-02-08T07:28:06.000000Z",
                                    "updated_at"           => "2024-02-08T07:28:06.000000Z",
                                ],
                            ],
                            "display_name"                 => "03 فرع أبحر",
                            "logo"                         => false,
                            "address"                      => "Jeddah",
                            "website"                      => null,
                            "location_custom_fields"       => "",
                            "contact"                      => "",
                            "customer_info"                => "",
                            "customer_tax_number"          => "",
                            "customer_tax_label"           => "",
                            "customer_custom_fields"       => "",
                            "client_id"                    => "CO0001",
                            "client_id_label"              => "",
                            "sales_person"                 => "",
                            "sales_person_label"           => "",
                            "call_no"                      => 1,
                            "invoice_no"                   => 1568,
                            "clientCompany"                => "",
                            "clientCompanyLabel"           => "App - تطبيقات",
                            "payment_status_label"         => "Status",
                            "payment_status"               => "paid",
                            "shipping_address"             => null,
                            "invoice_no_prefix"            => "Invoice No.",
                            "invoice_heading"              => "Invoice",
                            "date_label"                   => "Date",
                            "invoice_date"                 => "03/08/2024 14:20",
                            "hide_price"                   => true,
                            "lines"                        => [
                                [
                                    "cat_name"                   => "قسم الشاورما",
                                    "catID"                      => 23,
                                    "name"                       => "وجبة عربي صغير",
                                    "variation"                  => "",
                                    "product_variation"          => "",
                                    "quantity"                   => 1,
                                    "quantity_uf"                => 1,
                                    "units"                      => "Pc(s)",
                                    "unit_price"                 => "13.0400",
                                    "tax"                        => "0.00",
                                    "single_product_tax"         => 2.25,
                                    "single_product_tax_old"     => 2.25,
                                    "tax_id"                     => 1,
                                    "tax_unformatted"            => "0.0000",
                                    "tax_name"                   => "VAT",
                                    "tax_percent"                => 15,
                                    "tax_amount"                 => 2.25,
                                    "unit_price_inc_tax"         => "15.00",
                                    "unit_price_inc_tax_uf"      => 15,
                                    "sell_price_inc_tax"         => "15.00",
                                    "unit_price_exc_tax"         => "13.04",
                                    "price_exc_tax"              => "13.04",
                                    "sub_total_final"            => 15,
                                    "unit_price_before_discount" => "13.04",
                                    "sell_price_fnal"            => "15.0000",
                                    "line_total"                 => "15.00",
                                    "line_total_uf"              => 15,
                                    "line_discount"              => "0.00",
                                    "modifiers"                  => [
                                        [
                                            "name"                => "وجبة عربي صغير",
                                            "variation"           => "شطة عادي",
                                            "sell_price_inc_tax"  => "0.0000",
                                            "single_modifier_tax" => null,
                                            "tax_id"              => null,
                                            "tax_amount"          => 0,
                                            "tax_type"            => null,
                                            "modifier_tax"        => 0,
                                            "quantity"            => 1,
                                            "units"               => "Pc(s)",
                                            "unit_price_inc_tax"  => "0.0000",
                                            "unit_price_exc_tax"  => "0.00",
                                            "price_exc_tax"       => 0,
                                            "sub_total_final"     => 0,
                                            "line_total"          => "0.00",
                                        ],
                                        [
                                            "name"                => "وجبة عربي صغير",
                                            "variation"           => "مايونيز",
                                            "sell_price_inc_tax"  => "0.0000",
                                            "single_modifier_tax" => null,
                                            "tax_id"              => null,
                                            "tax_amount"          => 0,
                                            "tax_type"            => null,
                                            "modifier_tax"        => 0,
                                            "quantity"            => 1,
                                            "units"               => "Pc(s)",
                                            "unit_price_inc_tax"  => "0.0000",
                                            "unit_price_exc_tax"  => "0.00",
                                            "price_exc_tax"       => 0,
                                            "sub_total_final"     => 0,
                                            "line_total"          => "0.00",
                                        ],
                                        [
                                            "name"                => "وجبة عربي صغير",
                                            "variation"           => "اللمة جبنة",
                                            "sell_price_inc_tax"  => "3.0000",
                                            "single_modifier_tax" => 0.391305,
                                            "tax_id"              => 1,
                                            "tax_amount"          => 15,
                                            "tax_type"            => "inclusive",
                                            "modifier_tax"        => 3.001305,
                                            "quantity"            => 1,
                                            "units"               => "Pc(s)",
                                            "unit_price_inc_tax"  => "2.6087",
                                            "unit_price_exc_tax"  => "2.61",
                                            "price_exc_tax"       => 2.6087,
                                            "sub_total_final"     => 3.000005,
                                            "line_total"          => "2.61",
                                        ],
                                    ],
                                ],
                                [
                                    "cat_name"                   => "المشروبات",
                                    "catID"                      => 27,
                                    "name"                       => "شاني",
                                    "variation"                  => "",
                                    "product_variation"          => "",
                                    "quantity"                   => 1,
                                    "quantity_uf"                => 1,
                                    "units"                      => "Pc(s)",
                                    "unit_price"                 => "2.6100",
                                    "tax"                        => "0.00",
                                    "single_product_tax"         => 0.45,
                                    "single_product_tax_old"     => 0.45,
                                    "tax_id"                     => 1,
                                    "tax_unformatted"            => "0.0000",
                                    "tax_name"                   => "VAT",
                                    "tax_percent"                => 15,
                                    "tax_amount"                 => 0.45,
                                    "unit_price_inc_tax"         => "3.00",
                                    "unit_price_inc_tax_uf"      => 3,
                                    "sell_price_inc_tax"         => "3.00",
                                    "unit_price_exc_tax"         => "2.61",
                                    "price_exc_tax"              => "2.61",
                                    "sub_total_final"            => 3,
                                    "unit_price_before_discount" => "2.61",
                                    "sell_price_fnal"            => "3.0000",
                                    "line_total"                 => "3.00",
                                    "line_total_uf"              => 3,
                                    "line_discount"              => "0.00",
                                ],
                            ],
                            "receipt_printer_type"         => "browser",
                            "number_of_thermal_print_copy" => "1",
                            "appliedTax"                   => [
                                [
                                    "tax_name"    => "VAT",
                                    "tax_percent" => 15,
                                    "tax_id"      => 1,
                                    "tax_amount"  => 2.7,
                                ],
                            ],
                            "modifier_tax"                 => 0.391305,
                            "taxes"                        => [
                                "VAT" => "﷼ 0.00",
                            ],
                            "total_quantity_label"         => "Products  Count",
                            "total_quantity"               => "2.00",
                            "show_cat_code"                => 0,
                            "cat_code_label"               => "HSN",
                            "subtotal_label"               => "Subtotal:",
                            "subtotal"                     => "﷼ 20.61",
                            "subtotal_unformatted"         => "20.6087",
                            "round_off_label"              => "Round Off:",
                            "round_off"                    => "﷼ 0.00",
                            "round_off_amount"             => "0.0000",
                            "total_exempt"                 => "﷼ 0.00",
                            "total_exempt_uf"              => 0,
                            "taxed_subtotal"               => "﷼ 20.61",
                            "line_discount_label"          => "Discount",
                            "discount_label"               => "Discount",
                            "discount_amount"              => "﷼ 0.00",
                            "discount"                     => 0,
                            "transaction_tax"              => [
                                "id"                      => 1,
                                "business_id"             => 2,
                                "name"                    => "VAT",
                                "amount"                  => 15,
                                "is_tax_group"            => 0,
                                "for_tax_group"           => 0,
                                "created_by"              => 2,
                                "woocommerce_tax_rate_id" => null,
                                "deleted_at"              => null,
                                "created_at"              => "2021-02-14T09:51:03.000000Z",
                                "updated_at"              => "2021-02-14T09:51:03.000000Z",
                                "sub_taxes"               => [
                                ],
                            ],
                            "tax_label"                    => "Tax (VAT):",
                            "line_tax_label"               => "Tax",
                            "tax"                          => "﷼ 3.09",
                            "group_tax_details"            => [
                            ],
                            "shipping_charges"             => 0,
                            "shipping_charges_label"       => "Shipping Charges",
                            "shipping_details"             => null,
                            "shipping_details_label"       => "Shipping Details",
                            "packing_charge_label"         => "Packing Charge",
                            "packing_charge"               => 0,
                            "total_label"                  => "Total:",
                            "total"                        => "﷼ 21.00",
                            "final_total"                  => 21,
                            "total_paid"                   => "﷼ 21.00",
                            "total_paid_label"             => "Total paid",
                            "total_due"                    => "0 ﷼",
                            "total_due_label"              => "Due",
                            "payments"                     => [
                                [
                                    "method" => "Cash - كاش",
                                    "amount" => "﷼ 21.00",
                                    "date"   => "03/08/2024",
                                ],
                            ],
                            "payment_types"                => [
                                "advance"      => "Advance",
                                "cash"         => "Cash - كاش",
                                "card"         => "Card - شبكه",
                                "prepaid"      => "Prepaid -  مدفوع",
                                "custom_pay_1" => "Bank",
                            ],
                            "payment_method"               => "Cash - كاش",
                            "barcode"                      => false,
                            "additional_notes"             => null,
                            "footer_text"                  => "<p style=\"text-align: center;\">&nbsp;</p> <p style=\"text-align: center;\">شكرا لزيارتكم ...... نتشرف بزيارتكم مرة أخرى<br />رقم الجوال0542260631</p>",
                            "show_barcode"                 => false,
                            "table_label"                  => "Table",
                            "table"                        => "",
                            "service_staff_label"          => "Service staff",
                            "service_staff"                => "",
                            "design"                       => "slim",
                            "table_tax_headings"           => null,
                            "generatedBase64String"        => "ARPYtNin2YjYsdmF2Kcg2KfZhNmD",
                            "currency"                     => [
                                "symbol"             => "﷼",
                                "thousand_separator" => ",",
                                "decimal_separator"  => ".",
                            ],
                        ],
                    ],
                ];
                return response()->json(['success' => 1, ' receipt' => $invoicePrint], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);

        }
    }

    private function generateQrCode($businessName, $taxNumber, $invoiceDate, $invoiceTotalAmount, $invoiceTaxAmount, $transaction_id)
    {
        /*  $generatedString = GenerateQrCode::fromArray([
        new Seller($businessName), // seller name
        new TaxNumber($taxNumber), // seller tax number
        new InvoiceDate($invoiceDate), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
        new InvoiceTotalAmount($invoiceTotalAmount), // invoice total amount
        new InvoiceTaxAmount($invoiceTaxAmount), // invoice tax amount
        ])->toBase64(); */

        $generatedString = GenerateQrCode::fromArray([
            new Seller($businessName),                   // seller name
            new TaxNumber($taxNumber),                   // seller tax number
            new InvoiceDate($invoiceDate),               // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
            new InvoiceTotalAmount($invoiceTotalAmount), // invoice total amount
            new InvoiceTaxAmount($invoiceTaxAmount),     // invoice tax amount
                                                         // TODO :: Support others tags
        ])->render();

        Transaction::where('id', $transaction_id)->update(['generatedBase64String' => $generatedString]);
        return $generatedString;
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function getCategory(Request $request)
    {

        try {
            $input            = $request->all();
            $business_id      = $input['business_id'] ?? "";
            $location_id      = $input['location_id'] ?? "";
            $business_details = $this->businessUtil->getDetails($business_id);

            //If brands, category are enabled then send else false.
            $categories = ($business_details->enable_category == 1) ? Category::catAndSubCategories($business_id, $location_id) : [];
            if (($categories)) {

                return response()->json(['success' => 1, ' categories' => $categories], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function closeRegister(Request $request)
    {
        try {
            $input   = $request->all();
            $user_id = $input['user_id'] ?? "";
            $user    = User::where('id', $user_id)->with(['media'])->first();
            if (! $user->can('sell.pos.close_cash_register')) {
                return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to Close the register.'], 422);
            }

            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = [
                    'success' => 0,
                    'msg'     => 'Feature disabled in demo!!',
                ];
                return response()->json(['success' => 0, 'msg' => 'Feature disabled in demo!!'], 200);
            }

            $input                           = $request->only(['closing_amount', 'total_expense', 'remainder_cash_in_hand', 'total_card_slips', 'total_cheques', 'closing_note']);
            $input['closing_amount']         = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $input['total_expense']          = $this->cashRegisterUtil->num_uf($input['total_expense']);
            $input['remainder_cash_in_hand'] = $this->cashRegisterUtil->num_uf($input['remainder_cash_in_hand']);
            $input['closed_at']              = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status']                 = 'close';

            CashRegister::where('user_id', $user_id)
                ->where('status', 'open')
                ->update($input);
            $output = [
                'success' => 1,
                'msg'     => __('cash_register.close_success'),
            ];
            return response()->json(['success' => 1, 'msg' => __('cash_register.close_success')], 200);

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);

        }

    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getRecentTransactions(Request $request)
    {
        try {
            $input = $request->all();
            //return response()->json(['success' => 1, 'input' => $input], 200);
            

            $business_id          = $input['business_id'] ?? "";
            $user_id              = $input['user_id'] ?? "";
            $transaction_status   = $input['status'] ?? "";
            $transaction_sub_type = $input['transaction_sub_type'] ?? "";
            \Log::emergency("RecentTransaction Functions executed UserID: " .$user_id . " business_id: " . $business_id );
            $user                 = User::where('id', $user_id)->with(['media'])->first();

            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);
                if (! $user->can('sell.pos.add')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not allowed to add POS sell.'], 422);
                }
                if (! $user->can('sell.pos.view')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not allowed to view POS Sell.'], 422);
                }
                $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id, $business_id);
                if (! empty($register)) {
                    $created_date         = $register->created_at;
                    $register->created_at = $register->created_at;
                } else {
                    $created_date = Carbon::today()->toDateString() . ' 12:00:00';
                }
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

                if (! empty($transaction_sub_type)) {
                    $query->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $query->where('transactions.sub_type', null);
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
                    ->limit(200)
                    ->get();
                $printer_type     = 'browser';
                $business_details = $this->businessUtil->getDetails($business_id);
                $sql              = $query->toSql();
                $bindings         = $query->getBindings();
                $print            = [];
                foreach ($transactions as $transaction) {
                    $location_details                            = BusinessLocation::find($transaction->location_id);
                    $invoice_layout_id                           = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
                    $invoice_layout                              = $this->businessUtil->invoiceLayout($business_id, $transaction->location_id, $invoice_layout_id);
                    $receipt_printer_type                        = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;
                    $transaction['print']                        = $this->transactionUtil->getReceiptDetails($transaction->id, $transaction->location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $business_id);
                    $transaction['print']->call_no               = $transaction->call_no;
                    $transaction['print']->generatedBase64String = $transaction->generatedBase64String;
                    $transaction['print']->created_at            = Carbon::parse($transaction->created_at)->format('Y-m-d H:i A');

                }
                //return response()->json(['success' => 1, 'print' => $transaction['print']], 200);
                return response()->json(['success' => 1, 'transactions' => $transactions, 'transaction_sub_type' => $transaction_sub_type, 'query' => $sql, 'bindings' => $bindings, 'register' => $register], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            return response()->json(['success' => 0, 'msg' => $msg], 500);
        }
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function getRegisterDetails(Request $request)
    {
        try {
            $input              = $request->all();
            $business_id        = $input['business_id'] ?? "";
            $user_id            = $input['user_id'] ?? "";
            $input_cash_in_hand = $input['input_cash_in_hand'] ?? "";

            CashRegister::where('user_id', $user_id)->where('status', 'open')->where('business_id', $business_id)->update(['input_cash_in_hand' => $input_cash_in_hand]);
            $user = User::where('id', $user_id)->with(['media'])->first();
            if (! $user->can('sell.pos.view_cash_register')) {
                return response()->json(['success' => 0, 'errors' => __('Unauthorized action! Not alowed to view Cash register.')], 422);
            }
            $register_details_array = $this->cashRegisterUtil->getRegisterDetails(null, $business_id, $user_id);
            $register_details       = $register_details_array['register_details'];
            if (! empty($register_details['user_id'])) {
                $open_time                   = $register_details['open_time'] ?? "";
                $close_time                  = \Carbon::now()->toDateTimeString();
                $is_types_of_service_enabled = $this->commonUtil->isModuleEnabled('direct_sales_module', $business_id);
                $details                     = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time,
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
                $total_cash                         = $register_details['total_cash'] ?? 0.00;
                $total_app_cash                     = $register_details['total_app_cash'] ?? 0.00;
                $all_cash_new                       = ($total_cash + $total_app_cash) ?? 0.00;
                $total_app_card                     = $register_details['total_app_card'] ?? 0.00;
                $all_card_new                       = ($total_card + $total_app_card) ?? 0.00;
                $clientApp                          = ClientCompany::where('business_id', $business_id)->get();
                $clientAppArray                     = [];
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
                return response()->json([
                    'success'             => 1,
                    'register_details'    => $register_details,
                    'details'             => $details,
                    'payment_types'       => $payment_types,
                    'close_time'          => $close_time,
                    'sql'                 => $register_details_array['sql'],
                    'bindings'            => $register_details_array['bindings'],
                    "appClientNameSelect" => $register_details_array['appClientNameSelect'],
                    "appClientNameArray"  => $register_details_array['appClientNameArray'],
                    "appClientName"       => $register_details_array['appClientName'],
                ], 200);
            } else {
                return response()->json(['success' => 0, 'errors' => __('Unauthorized action! Cash register is empty.')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => $e->getMessage()], 500);

        }
    }

    public function searchCustomer(Request $request)
    {
        try {
            $input       = $request->all();
            $business_id = $input['business_id'] ?? "";
            $user_id     = $input['user_id'] ?? "";
            $term        = $input['query'] ?? "";
            $enable_rp   = $input['enable_rp'] ?? 0;
            $contacts    = Contact::where('business_id', $business_id)->active();
            //$contacts = Contact::where('business_id', $business_id)->first();

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

            /*  $contacts->select(
            'contacts.id',
            DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')')) AS text"),
            'mobile',
            'address_line_1',
            'city',
            'state',
            'pay_term_number',
            'pay_term_type',
            'balance',
            'supplier_business_name'
            )->onlyCustomers(); */
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
            );
            $contacts->where('contacts.type', 'customer');

            if ($enable_rp == 1) {
                $contacts->addSelect('total_rp');
            }
            $contactData = $contacts->get();
            $sql         = $contacts->toSql();
            $bindings    = $contacts->getBindings();

            return response()->json(['success' => 1, 'bindings' => $bindings, 'sql' => $sql, 'contacts' => $contactData], 200);

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => $e->getMessage()], 500);

        }
    }
    public function storeCustomer(Request $request)
    {
        $input       = $request->all();
        $business_id = $input['business_id'] ?? "";
        $user_id     = $input['user_id'] ?? "";
        $name        = $input['name'] ?? "";
        $mobile      = $input['mobile'] ?? "";
        $user        = User::where('id', $user_id)->with(['media'])->first();
        if (! $user->can('sell.pos.customer.add')) {
            return response()->json(['success' => 0, 'errors' => 1, 'msg' => __('Unauthorized action! Not alowed add customer from POS.')], 422);
        }
        try {
            $input['created_by']     = $user_id;
            $input['type']           = 'customer';
            $input['contact_status'] = 'active';

            $output = $this->contactUtil->createNewContact($input);
            return response()->json(['success' => 1, 'data' => $output], 200);

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg'                => __("messages.something_went_wrong"),
            ];
        }

    }

    public function getAppUpgeadation(Request $request)
    {
        try {
            $output = [
                'android' => [
                    'OS'         => 'android',
                    'mustUpdate' => 1,
                ],
                'IOS'     => [
                    'OS'         => 'IOS',
                    'mustUpdate' => 1,
                ],

            ];
            return response()->json($output, 200);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }

    }

}
