<?php
namespace App\Http\Controllers\Api;

use App;
use App\Account;
use App\Business;
use App\BusinessLocation;
use App\CallNumberScheme;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Http\Controllers\Controller;
use App\InvoiceLayout;
use App\Media;
use App\Product;
use App\Restaurant\ResTable;
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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Utils\ManufacturingUtil;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use Spatie\Permission\Models\Role;

class ApiProductController extends Controller
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
        NotificationUtil $notificationUtil,
        ModuleUtil $moduleUtil,
        BusinessUtil $businessUtil,
        ProductUtil $productUtil,
        ContactUtil $contactUtil,
        TransactionUtil $transactionUtil,
        Util $commonUtil,
        CashRegisterUtil $cashRegisterUtil,
        ManufacturingUtil $mfgUtil
    ) {
        $this->businessUtil     = $businessUtil;
        $this->productUtil      = $productUtil;
        $this->contactUtil      = $contactUtil;
        $this->transactionUtil  = $transactionUtil;
        $this->commonUtil       = $commonUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil       = $moduleUtil;
        $this->mfgUtil          = $mfgUtil;

        $this->notificationUtil = $notificationUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount'    => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return'                         => 0, 'transaction_no' => ''];

    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getServiceStaffs(Request $request)
    {
        try {
            $input       = $request->all();
            $business_id = $input['business_id'] ?? "";
            $location_id = $input['location_id'] ?? "";
            if (! empty($location_id)) {
                $waiters_enabled = false;
                $waiters         = null;
                if ($this->commonUtil->isModuleEnabledAppUser('service_staff_module', $business_id)) {
                    $waiters_enabled = true;
                    $waiters         = [];
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
            $business                  = Business::where('id', $business_id)->first();
            $pos_settings              = json_decode($business->pos_settings, true);
            $is_service_staff_required = (! empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : [];
            return response()->json(['success' => 1, 'input' => $input, 'service_staff' => $waiters, 'service_staff_enabled' => $waiters_enabled, 'is_service_staff_required' => $is_service_staff_required], 200);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * @param  Request  $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getTables(Request $request)
    {
        try {
            $input       = $request->all();
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $location_id = $input['location_id'] ?? "";
            if (! empty($location_id)) {
                $tables_enabled = false;
                $tables         = null;
                if ($this->commonUtil->isModuleEnabled('table_module', $business_id)) {
                    $tables_enabled = true;
                    $tables         = ResTable::where('business_id', $business_id)->where('location_id', $location_id)->get();
                }
            } else {
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
            return response()->json(['success' => 1, 'tables_enabled' => $tables_enabled, 'tables' => $tables_data], 200);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function getCategory(Request $request)
    {
        try {
            $input       = $request->all();
            $business_id = $input['business_id'] ?? "";
            $location_id = $input['location_id'] ?? "";
            $business    = Business::where('id', $business_id)->first();
            if (($business)) {
                //If brands, category are enabled then send else false.
                $categories = ($business->enable_category == 1) ? Category::catAndSubCategories($business_id, $location_id) : [];
                if (($categories)) {
                    return response()->json(['success' => 1, ' categories' => $categories], 200);
                } else {
                    return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
                }
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * @return Application|ResponseFactory|Response
     */

    public function getProducts(Request $request)
    {
        try {
            $input = $request->all();
            // return response()->json(['success' => 1, 'user_id' => $input], 200);
            $category_id    = $input['category_id'] ?? "";
            $business_id    = $input['business_id'] ?? "";
            $location_id    = $input['location_id'] ?? "";
            $brand_id       = $input['brand_id'] ?? "";
            $is_favourite   = $input['is_favourite'] ?? "";
            $term           = $input['term'] ?? "";
            $is_direct_sell = $input['is_direct_sell'] ?? "";
            $customer_id    = $input['customer_id'] ?? "";
            $price_group    = $input['price_group'] ?? "";
            $user_id        = $input['user_id'] ?? "";
            $user           = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.add')) {
                    Log::info("getProducts: Unauthorized action! Not alowed add POS sell");
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed add POS sell.'], 403);
                }
                if (! $this->commonUtil->isModuleEnabled('product_module', $business_id) && ! $is_direct_sell) {
                    Log::info("getProducts: Unauthorized action! Not alowed to access product module");
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to access product module.'], 403);
                }
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }
            $check_qty         = false;
            $business_location = BusinessLocation::where('id', $location_id)->first();
            $business_details  = $this->businessUtil->getDetails($business_id);
            $business          = Business::where('id', $business_id)->first();
            $common_settings   = $business->common_settings ?? "";
            $pos_settings      = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
            $products          = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')
                ->leftjoin('category_locations as cl', 'cl.category_id', '=', 'p.category_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');
                        //Include Location
                        if (! empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });
                        }
                    }
                )
                ->where('cl.location_id', $location_id)
                ->where('p.business_id', $business_id)
                ->where('p.type', '!=', 'modifier')
                ->where('p.is_inactive', 0)
                ->where('p.not_for_selling', 0)
                ->where(function ($q) use ($location_id) {
                    $q->where('pl.location_id', $location_id);
                });

            if ($is_favourite == 1) {
                if ($business_location->featured_products != null) {
                    $products->whereIn('variations.id', $business_location->featured_products);
                }
            }
            /* Include search */
            if (! empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('p.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
                });
            }

            /*  Include check for quantity */
            if ($check_qty) {
                $products->where('VLD.qty_available', '>', 0);
            }

            if (! empty($category_id) && ($category_id != 'all')) {
                $products->where(function ($query) use ($category_id) {
                    $query->where('p.category_id', $category_id);
                    $query->orWhere('p.sub_category_id', $category_id);
                });
            }

            if (! empty($brand_id) && ($brand_id != 'all')) {
                $products->where('p.brand_id', $brand_id);
            }

            if (! empty($request->get('is_enabled_stock'))) {
                $is_enabled_stock = 0;
                if ($request->get('is_enabled_stock') == 'product') {
                    $is_enabled_stock = 1;
                }

                $products->where('p.enable_stock', $is_enabled_stock);
            }

            if (! empty($request->get('repair_model_id'))) {
                $products->where('p.repair_model_id', $request->get('repair_model_id'));
            }
            $sql      = $products->toSql();
            $bindings = $products->getBindings();

            $products = $products->select(
                'p.id as product_id',
                'p.id',
                'p.name',
                'p.sku',
                'p.name AS product_name',
                'p.name as product_actual_name',
                'p.category_id',
                'p.brand_id',
                'p.tax as tax_id',
                'p.tax_type',
                'p.enable_stock',
                'p.enable_sr_no',
                'p.type as product_type',
                'p.warranty_id',
                'variations.sell_price_inc_tax',
                'p.type',
                'p.enable_stock',
                'p.image as product_image',
                'pv.name as product_variation_name',
                'pv.is_dummy as is_dummy',
                'units.short_name as unit',
                'units.id as unit_id',
                'units.allow_decimal as unit_allow_decimal',
                'variations.id as variations_id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.barcode_type',
                'variations.default_sell_price',
                'variations.id as variation_id',
                'variations.combo_variations'
            )
                ->with(['media', 'product'])
                ->orderBy("p.sku", 'asc')
                ->get();

            $results = [];

            $products->map(function ($variation) use (&$results, $business_details, $business_id, $common_settings, $pos_settings, $location_id, $customer_id, $is_direct_sell, $user) {
                $product                      = $variation->product;
                $variation->product_image_url = $product->image_url;

                /*  Product base Tax applied */
                if (! empty($variation->product->tax) && $variation->product->tax_type == 'inclusive') {
                    $tax = TaxRate::where('business_id', $business_id)->where('id', $variation->product->tax)->first();
                    if (! empty($tax)) {
                        $tax_percent              = (($tax->amount + 100) / 100);
                        $tax_percent_multiply     = ($tax->amount / 100);
                        $sell_price               = ($variation->sell_price_inc_tax / $tax_percent);
                        $product_tax              = ($sell_price * $tax_percent_multiply);
                        $variation['product_tax'] = number_format($product_tax, 2, '.', ',');
                        $variation['tax_name']    = $tax->name;
                        $variation['tax_amount']  = $tax->amount;
                        $variation['tax_type']    = $variation->product->tax_type;
                    }
                }
                if (! empty($variation->product)) {
                    $custom_product_price = DB::table('custom_product_price')->where('product_id', $variation->product_id)->where('business_id', $location_id)->first();
                    if (! empty($custom_product_price)) {
                        $variation->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $variation->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $variation->unit_price         = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $variation->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    }

                }

                /*  Get customer group and change the price accordingly */
                $cg                            = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
                $percent                       = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
                $variation->default_sell_price = (float) number_format($variation->default_sell_price + ($percent * $variation->default_sell_price / 100), 2);
                $variation->sell_price_inc_tax = (float) $variation->sell_price_inc_tax + ($percent * $variation->sell_price_inc_tax / 100);
                $variation->unit_price         = (float) $variation->default_sell_price + ($percent * $variation->default_sell_price / 100);
                $variation->unit_price_inc_tax = (float) $variation->sell_price_inc_tax + ($percent * $variation->sell_price_inc_tax / 100);

                $variation->default_sell_price = number_format($variation->default_sell_price, 2, '.', ',');
                $variation->sell_price_inc_tax = number_format($variation->sell_price_inc_tax, 2, '.', ',');
                $variation->unit_price         = number_format($variation->unit_price, 2, '.', ',');
                $variation->unit_price_inc_tax = number_format($variation->unit_price_inc_tax, 2, '.', ',');

                $is_cg    = ! empty($cg->id) ? true : false;
                $is_pg    = ! empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($variation, $business_id, $location_id, $is_cg, $is_pg, $variation->variation_id);
                if ($user) {
                    if ($is_direct_sell) {
                        $edit_discount = $user->can('sell.pos.add_product_discount_from_pos_screen');
                        $edit_price    = $user->can('sell.pos.edit_product_price_from_pos_screen');
                    } else {
                        $edit_discount = $user->can('sell.pos.add_product_discount_from_pos_screen');
                        $edit_price    = $user->can('sell.pos.edit_product_price_from_pos_screen');
                    }
                    $variation['edit_discount'] = $edit_discount;
                    $variation['edit_price']    = $edit_price;

                }
                if ($discount == null) {
                    $discount = [
                        'id'              => null,
                        'name'            => null,
                        "discount_type"   => null,
                        "discount_amount" => null,
                    ];
                }

                /*  Product base Tax applied */
                $tax_dropdown = null;

                if (($variation->tax_id) && ($variation->tax_type == 'inclusive')) {
                    $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $variation->tax_id)->first();
                }
                $allTaxDropdown               = TaxRate::where('business_id', $business_id)->get();
                $enabled_modules              = $this->commonUtil->allModulesEnabled($business_id);
                $variation['discount']        = $discount;
                $variation['enabled_modules'] = $enabled_modules;
                $variation['tax_dropdown']    = $tax_dropdown;
                $variation['allTaxDropdown']  = $allTaxDropdown;
                $variation['common_settings'] = $common_settings;
                $variation['pos_settings']    = $pos_settings;

                unset($variation->product);
                $results[] = $variation;
            });

            if (! empty($products)) {
                foreach ($products as $key => $pr) {
                    $products[$key]['modifier_products'] = [];
                    $product_id                          = $pr->product_id ?? "";
                    $custom_product_price                = DB::table('custom_product_price')->where('product_id', $product_id)->where('business_id', $location_id)->first();
                    if (! empty($custom_product_price)) {
                        $products[$key]->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->unit_price         = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->product_tax        = (float) $custom_product_price->custom_price_applicable_tax ?? "";
                    }

                    $this_product = Product::where('business_id', $business_id)->find($product_id);
                    if (! empty($this_product)) {
                        if (count($this_product->modifier_sets) > 0) {
                            $product_ms                          = $this_product->modifier_sets;
                            $products[$key]['modifier_products'] = $product_ms;
                            foreach ($product_ms as $keym => $modifier_set) {
                                foreach ($modifier_set->variations as $modifier) {
                                    $products[$key]['modifier_products'][$keym]['variations'] = $modifier;
                                    if (! is_null($modifier->tax_id) && $modifier->tax_type == 'inclusive') {
                                        $products[$key]['modifier_products'][$keym]['variations']['tax_dropdown'] = TaxRate::where('id', $modifier->tax_id)->first();
                                    } else {
                                        $products[$key]['modifier_products'][$keym]['variations']['tax_dropdown'] = null;
                                    }
                                }
                            }
                        }
                    }

                }
            }
            return response()->json(['success' => 1, 'products' => $products], 200);

        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = "File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage();
            return response()->json(['success' => 0, 'msg' => $msg], 500);
        }
    }

    /**
     * @return array
     *
     * @throws \Throwable
     */
    public function getProductRow(Request $request)
    {
        $output = [];
        $input  = $request->all();

        //return response()->json(['success' => 1, 'input' => $input], 200);

        try {

            $business_id            = $input['business_id'] ?? "";
            $is_direct_sell         = $input['is_direct_sell'] ?? "";
            $quantity               = $input['quantity'] ?? 1;
            $weighing_scale_barcode = $input['weighing_scale_barcode'] ?? "";
            $variation_id           = $input['variation_id'] ?? "";
            $location_id            = $input['location_id'] ?? "";
            $row_count              = $input['product_row'] ?? 0;
            $enable_lot_number      = $input['enable_lot_number'] ?? 0;
            $enable_product_expiry  = $input['enable_product_expiry'] ?? 0;
            $user_id                = $input['user_id'] ?? "";
            $user                   = User::where('id', $user_id)->with(['media'])->first();
            $enable_inline_tax      = $input['enable_inline_tax'] ?? 0;

            $business        = Business::where('id', $business_id)->first();
            $common_settings = $business->common_settings ?? "";

            $row_count      = $row_count + 1;
            $is_direct_sell = false;
            if ($is_direct_sell == 'true') {
                $is_direct_sell = true;
            }

            //Check for weighing scale barcode
            if ($variation_id == 'null' && ! empty($weighing_barcode)) {
                $product_details = $this->__parseWeighingBarcode($weighing_barcode);
                if ($product_details['success']) {
                    $variation_id = $product_details['variation_id'];
                    $quantity     = $product_details['qty'];
                } else {
                    $output['success'] = false;
                    $output['msg']     = $product_details['msg'];
                    return $output;
                }
            }

            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
            //return response()->json(['success' => 1, 'product_details' => $pos_settings], 200);

            $check_qty = ! empty($pos_settings['allow_overselling']) ? false : true;
            $product   = $this->productUtil->getDetailsFromProductVariation($variation_id, $business_id, $location_id, $check_qty);
            //return response()->json(['product' => $product], 200);

            if (! isset($product->quantity_ordered)) {
                $product->quantity_ordered = $quantity;
            }

            if ($product->qty_available) {
                $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);
            }

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);

            //Get customer group and change the price accordingly
            $customer_id                 = request()->get('customer_id', null);
            $cg                          = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent                     = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            // Product base Tax applied
            $tax_dropdown = null;
            if (! empty($product->tax_id) && $product->tax_type == 'inclusive') {
                $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $product->tax_id)->first();
            }
            $allTaxDropdown  = TaxRate::where('business_id', $business_id)->get();
            $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if ($enable_lot_number == 1 || $enable_product_expiry == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[]            = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;
            $product->item_tax    = 0.00;
            $purchase_line_id     = request()->get('purchase_line_id');
            $price_group          = request()->input('price_group');
            if (! empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

                if (! empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }
            $warranties = $this->__getwarranties($business_id, $common_settings);
            $enabledTax = false;
            if ($enable_inline_tax == 1) {
                $enabledTax = true;
            }
            $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;
            $waiters             = [];
            if ($this->commonUtil->isModuleEnabled('service_staff_module', $business_id) && ! empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters         = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }
            $product['quantity']           = $quantity;
            $product['unit_price']         = $product->default_sell_price;
            $product['unit_price_inc_tax'] = $product->sell_price_inc_tax;

            if (! empty($product)) {
                $custom_product_price = DB::table('custom_product_price')->where('product_id', $product->product_id)->where('business_id', $location_id)->first();
                if (! empty($custom_product_price)) {
                    $product->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                    $product->unit_price         = (float) $custom_product_price->custom_price_exc_tax ?? "";
                    $product->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    $product->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                }

            }

            if (request()->get('type') == 'sell-return') {
                $output['product_row'] = ['product' => $product, 'row_count' => $row_count, 'tax_dropdown' => $tax_dropdown, 'all_tax_dropdown' => $allTaxDropdown, 'enabled_modules' => $enabled_modules, 'enabled_tax' => $enabledTax, 'enabled_warranties' => $is_warranty_enabled, 'sub_units' => $sub_units];
            } else {
                $is_cg    = ! empty($cg->id) ? true : false;
                $is_pg    = ! empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg, $variation_id);
                if ($is_direct_sell) {
                    $edit_discount = $user->can('sell.pos.add_product_discount_from_pos_screen');
                    $edit_price    = $user->can('sell.pos.edit_product_price_from_pos_screen');
                } else {
                    $edit_discount = $user->can('sell.pos.add_product_discount_from_pos_screen');
                    $edit_price    = $user->can('sell.pos.add_product_discount_from_pos_screen');
                }

                if ($discount == null) {
                    $discount = [
                        'id'              => null,
                        'name'            => null,
                        "discount_type"   => null,
                        "discount_amount" => null,
                    ];
                }
                $output['product_row'] = ['product' => $product, 'row_count' => $row_count, 'tax_dropdown' => $tax_dropdown, 'all_tax_dropdown' => $allTaxDropdown, 'enabled_modules' => $enabled_modules, 'pos_settings' => $pos_settings, 'sub_units' => $sub_units, 'discount' => $discount, 'waiters' => $waiters, 'enabled_tax' => $enabledTax, 'enabled_warranties' => $is_warranty_enabled, 'edit_discount' => $edit_discount, 'edit_price' => $edit_price, 'purchase_line_id' => $purchase_line_id, 'warranties' => $warranties, 'quantity' => $quantity];
            }

            $output['enable_sr_no'] = $product->enable_sr_no;

            $output['modifier_products'] = [];
            //return response()->json(['success' => 1, 'output' => $output], 200);

            if ($this->commonUtil->isModuleEnabled('product_module', $business_id) && ! $is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms                  = $this_product->modifier_sets;
                    $output['modifier_products'] = $product_ms;
                    //return response()->json(['success' => 1, 'product_ms' => $product_ms], 200);

                    foreach ($product_ms as $key => $modifier_set) {
                        foreach ($modifier_set->variations as $modifier) {
                            $modifier->item_tax                              = ($modifier->sell_price_inc_tax - $modifier->default_sell_price);
                            $output['modifier_products'][$key]['variations'] = $modifier;
                            if (! is_null($modifier->tax_id) && $modifier->tax_type == 'inclusive') {
                                $output['modifier_products'][$key]['variations']['tax_dropdown'] = TaxRate::where('id', $modifier->tax_id)->first();
                            } else {
                                $output['modifier_products'][$key]['variations']['tax_dropdown'] = null;
                            }
                        }
                    }
                }
            }
            return response()->json(['success' => 1, 'is_direct_sell' => $is_direct_sell, 'product' => $output], 200);

        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            //$msg = __('messages.something_went_wrong');
            return response()->json(['success' => 0, 'msg' => $msg], 500);
        }

    }

    private function __getwarranties($business_id, $common_settings)
    {
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties          = $is_warranty_enabled ? Warranty::where('business_id', $business_id)
            ->get() : [];
        return $warranties;
    }

    public function suspendedSellNote(Request $request)
    {
        try {
            $input = $request->all();
            //return response()->json(['success' => 1, 'input' => $input], 200);
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $user        = User::where('id', $user_id)->with(['media'])->first();
            //Log::info("suspendedSellNote   businessID:" . $business_id . " userid:" . $user_id);
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
                        $this->productUtil
                        ->decreaseProductQuantityCombo(
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

                        if ($request->get('enable_rp') == 1) {
                            $redeemed = ! empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                            $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                        }

                        //Allocate the quantity from purchase and add mapping of
                        //purchase & sell lines in
                        //transaction_sell_lines_purchase_lines table
                        $business_details = $this->businessUtil->getDetails($business_id);
                        $pos_settings     = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                        $business = ['id'   => $business_id,
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
                    $output = ['success' => 1, 'msg' => $msg, 'print_invoice' => $print_invoice, 'receipt' => $receipt];

                } else {
                    $output = ['success' => 0,
                        'msg'                => trans("messages.something_went_wrong"),
                    ];
                }
                return response()->json($output, 200);

            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);
            //$msg = trans("messages.something_went_wrong");
            $msg    = $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id;
            $output = ['success' => 0, 'msg' => $msg, 'receipt' => null,
            ];
            return response()->json($output, 500);
        }

    }
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

    public function getSuspendedSells(Request $request)
    {

        try {
            $input = $request->all();

            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $location_id = $input['location_id'] ?? "";
            //Log::info("getSuspendedSells businessID:" . $business_id . " userid:" . $user_id);

            $user = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! $user->can('sell.pos.view')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized  action! Not alowed view POS sell.'], 403);
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
            $sells->where('transactions.created_at', '>=', $created_date);
            $sells->whereNull('transactions.deliverect_order_id');

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
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id',
                        'transactions.additional_notes')
                    ->get();

                $sql      = $sells->toSql();
                $bindings = $sells->getBindings();

                $salesIds = $sales->pluck('id')->toArray();

                $printer_type     = 'browser';
                $business_details = $this->businessUtil->getDetails($business_id);

                $print = [];
                foreach ($sales as $transaction) {

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
                return response()->json(['success' => 1, ' sales' => $sales, 'is_service_staff_enabled' => $is_service_staff_enabled, 'transaction_sub_type' => $transaction_sub_type, 'salesIds' => $salesIds], 200);
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
            return response()->json($output, 500);
        }

    }

    public function editSuspendedSales(Request $request)
    {
        try {

            $input       = $request->all();
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $id          = $input['transaction_id'] ?? "";
            $edit_days   = $request->input('transaction_edit_days') ?? 30;
            $user        = User::where('id', $user_id)->with(['media'])->first();
            //Log::info("editSuspendedSales  businessID:" . $business_id . " userid:" . $user_id);

            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                if (! ($user->can('sell.pos.edit'))) {
                    return response(['errors' => 'Unauthorized  action! Not alowed edit POS sell.'], 422);
                }
            }
            /* $res = $this->transactionUtil->canBeEditedApi($id, $edit_days);
            if ($res && !($res->true)) {
            return response([
            'success' => 0, 'msg' => __($res->msg, ['days' => $edit_days]),
            ], 422);

            } */
            /* if (!$this->transactionUtil->canBeEditedApi($id, $edit_days)) {
            return response([
            'success' => 0, 'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]),
            ], 422);
            } */
            //Check if there is a open register, if no then redirect to Create Register screen.
            if ($this->cashRegisterUtil->countOpenedRegister($user_id) == 0) {
                return response()->json(['success' => 0, 'msg' => __('cash_register.register_not_opened')], 500);
            }
            //Check if return exist then not allowed
            if ($this->transactionUtil->isReturnExist($id)) {
                return response(['success' => 0, 'msg' => __('lang_v1.return_exist')], 422);
            }
            $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
            $business_details = $this->businessUtil->getDetails($business_id);
            $business         = Business::where('id', $business_id)->first();
            $common_settings  = $business->common_settings ?? "";

            $allTaxDropdown = TaxRate::where('business_id', $business_id)->get();
            $quantity       = request()->get('quantity', 1);

            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->with(['price_group', 'types_of_service'])
                ->findorfail($id);

            $location_id           = $transaction->location_id;
            $business_location     = BusinessLocation::find($location_id);
            $payment_types         = $this->productUtil->payment_types($business_location, true);
            $location_printer_type = $business_location->receipt_printer_type;

            $sell_details = TransactionSellLine::with([
                'product', 'product.variations', 'product.modifier_sets.variations', 'variations', 'modifiers',
            ])->where('transaction_id', $transaction->id)->where('parent_sell_line_id', '=', null)->get();
            $featured_products = $business_location->getFeaturedProducts();
            //return $sell_details;

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
            $location_id = $request->get('location_id');

            $categories = ($business_details->enable_category == 1) ? Category::catAndSubCategories($business_id, $location_id) : false;
            $brands     = ($business_details->enable_brand == 1) ? Brands::forDropdown($business_id)
                ->prepend(__('lang_v1.all_brands'), 'all') : false;

            $change_return = $this->dummyPaymentLine;

            $types = [];
            if ($user->can('supplier.create')) {
                $types['supplier'] = __('report.supplier');
            }
            if ($user->can('sell.pos.customer.add')) {
                $types['customer'] = __('report.customer');
            }
            if ($user->can('supplier.create')) {
                $types['both'] = __('lang_v1.both_supplier_customer');
            }
            $customer_groups = CustomerGroup::forDropdown($business_id);

            //Accounts
            $accounts = [];
            if ($this->commonUtil->isModuleEnabled('payment_account_module', $business_id)) {
                if (! ($user->can('account.access'))) {
                    $accounts = Account::forDropdownApi($business_id, true, false, true);
                }

            }

            $waiters = [];
            if ($this->commonUtil->isModuleEnabled('service_staff_module', $business_id) && ! empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters         = $this->productUtil->serviceStaffDropdown($business_id);
            }
            $redeem_details = [];

            if ($business_details->enable_rp == 1) {
                $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

                $redeem_details['points'] += $transaction->rp_redeemed;
                $redeem_details['points'] -= $transaction->rp_earned;
            }

            $edit_discount     = $user->can('sell.pos.add_product_discount_from_pos_screen');
            $edit_price        = $user->can('sell.pos.edit_product_price_from_pos_screen');
            $shipping_statuses = $this->transactionUtil->shipping_statuses();

            $warranties = $this->__getwarranties($business_id, $common_settings);
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
            if ($business_details->enable_inline_tax == 1) {
                $enabledTax = true;
            }
            $common_settings     = $business_details->common_settings;
            $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;

            $products      = [];
            $modifierTaxes = [];

            foreach ($sell_details as $key => $sell_detail) {
                $tax_dropdown = null;

                if (! empty($sell_detail->tax_id) && $sell_detail->product->tax_type == 'inclusive') {
                    $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $sell_detail->tax_id)->first();
                }
                $data['product_row']['product']                  = $sell_detail->product;
                $data['product_row']['product']['product_id']    = $sell_detail->product->id;
                $data['product_row']['product']['product_name']  = $sell_detail->product->name;
                $data['product_row']['product']['contact_id']    = $transaction->contact_id;
                $data['product_row']['product']['customer_name'] = $transaction->contact->name;
                $data['product_row']['product']['product_type']  = $sell_detail->product->type;
                $data['product_row']['product']['tax_id']        = $sell_detail->tax_id;
                $data['product_row']['quantity']                 = $sell_detail->quantity;
                $data['product_row']['tax_dropdown']             = $tax_dropdown;
                $data['product_row']['all_tax_dropdown']         = $allTaxDropdown;
                $data['product_row']['enabled_tax']              = $enabledTax;
                $data['product_row']['enabled_warranties']       = $is_warranty_enabled;
                $data['product_row']['pos_settings']             = $pos_settings;
                $data['product_row']['discount']                 = $discount;
                $data['product_row']['waiters']                  = $waiters;
                $data['product_row']['edit_discount']            = $edit_discount;
                $data['product_row']['edit_price']               = $edit_price;
                $data['product_row']['warranties']               = $warranties;
                $data['modifier_products']                       = [];
                $data['modifiers']                               = [];

                // Product Discount
                $is_cg                           = ! empty($cg->id) ? true : false;
                $is_pg                           = ! empty($price_group) ? true : false;
                $data['product_row']['discount'] = $this->productUtil->getProductDiscount($sell_detail, $business_id,
                    $location_id, $is_cg, $is_pg, $data['product_row']['product']['variation_id']);
                if ($data['product_row']['discount'] == null) {
                    $data['product_row']['discount'] = [
                        "discount_type"   => null,
                        "discount_amount" => null,
                    ];
                }

                $data['product_row']['discount']['discount_type']   = $sell_detail->line_discount_type;
                $data['product_row']['discount']['discount_amount'] = $sell_detail->line_discount_amount;

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
                    $data['product_row']['product']['product_variation_name'] = $variation->name;
                    $data['product_row']['product']['variation_name']         = $variation->name;
                    $data['product_row']['product']['variation_id']           = $variation->id;
                    $data['product_row']['product']['default_sell_price']     = $variation->default_sell_price;
                    $data['product_row']['product']['sell_price_inc_tax']     = $variation->sell_price_inc_tax;
                    $data['product_row']['product']['quantity']               = $sell_detail->quantity;
                    $data['product_row']['product']['unit_price_inc_tax']     = $sell_detail->unit_price_inc_tax;
                    $data['product_row']['product']['unit_price']             = $sell_detail->unit_price;
                    $data['product_row']['product']['sell_line_note']         = $sell_detail->sell_line_note;
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
                $data['product_row']['product']['modifier_tax'] = array_sum($modifierTaxes);
                $products[]                                     = $data;
            }

            $output                   = $data;
            $output['transaction_id'] = $id;

            return response()->json(['success' => 1, 'output' => $output], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg    = "File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage();
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
        }
    }

    public function updateSuspendedSales(Request $request)
    {
        $input       = $request->all();
        $user_id     = $input['user_id'] ?? "";
        $business_id = $input['business_id'] ?? "";
       //Log::info("updateSuspendedSales  businessID:" . $business_id . " userid:" . $user_id);

        try {
            $id         = $input['transaction_id'] ?? "";
            $contact_id = $input['contact_id'] ?? null;
            $user       = User::where('id', $user_id)->with(['media'])->first();

            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);

                if (! $user->can('sell.pos.edit')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to edit POS sell.'], 403);
                }

                if (! $user->can('sell.pos.view')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to view POS sell.'], 403);
                }

                $selectedBusiness = Business::where('id', $business_id)->first();
                //$enabled_modules = $selectedBusiness->enabled_modules ?? "";
                $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

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
                    $status_before      = $transaction_before->status;
                    $rp_earned_before   = $transaction_before->rp_earned;
                    $rp_redeemed_before = $transaction_before->rp_redeemed;

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

                    $commsn_agnt_setting = $business_details->sales_cmsn_agnt;

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
                    $input['clientCompany']       = request()->get('clientCompany') ?? "";
                    $input['order_type_Id']       = request()->get('order_type_Id') ?? "";
                    $input['is_created_from_api'] = 1;

                    //Begin transaction
                    DB::beginTransaction();

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
                        ->log('On Hold Sales updated by API. Transaction ID:' . $transaction->id);

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
                            $receipt = $this->receiptContentApi($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                        } else {
                            $receipt = '';
                        }
                    } elseif ($input['status'] == 'final') {
                        $msg = trans("sale.pos_sale_updated");
                        if (! $is_direct_sale) {
                            $receipt = $this->receiptContentApi($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
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
                return response()->json($output, 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);
            $msg    = "File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage();
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
        }
    }

    public function onHoldTransactionReturn(Request $request)
    {
        try {
            $input                 = $request->all();
            $transaction_id        = $request->get('transactionID');
            $location_id           = $request->get('location_id');
            $return_reason         = $request->get('return_reason');
            $return_payment_method = $request->get('return_payment_method');
            $card_transaction_id   = $request->get('card_transaction_id');
            $return_reason_other   = $request->get('return_reason_other');
            $items                 = $request->get('items') ?? '';
            $user_id               = $request->get('user_id');
            $business_id           = $request->get('business_id');
            $user                  = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);
                if (! $user->can('sell.pos.return')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to return POS sell.'], 422);
                }
                $return_reasom_array                          = [];
                $return_reasom_array['return_payment_method'] = $return_payment_method;
                $return_reasom_array['card_transaction_id']   = $card_transaction_id;
                if ($return_reason == 'other') {
                    $return_reasom_array['return_reason'] = $return_reason_other ?? "";
                } else {
                    $return_reasom_array['return_reason'] = $return_reason;
                }
                //Begin transaction
                DB::beginTransaction();
                $transaction = Transaction::where('id', $transaction_id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines', 'payment_lines'])
                    ->first();
                $location_id = $transaction->location_id;
                if (! empty($items)) {
                    foreach ($items as $item) {
                        $itemObj      = (object) $item;
                        $enable_stock = $itemObj->enable_stock;
                        $wastage      = $itemObj->wastage;

                        if (! empty($itemObj->sell_line_id) && ! empty($itemObj->quantity)) {
                            TransactionSellLine::where('id', $itemObj->sell_line_id)->update(['quantity_returned' => $itemObj->quantity]);
                            if ($wastage) {
                                $decrease_qty = ($itemObj->quantity_returned);
                            } else {
                                $decrease_qty = ($itemObj->quantity_returned);
                            }
                            if ($enable_stock) {
                                $this->productUtil->decreaseProductQuantity($itemObj->product_id, $itemObj->variation_id, $location_id, $decrease_qty);
                            } else {
                                $recipe = MfgRecipe::where('variation_id', $itemObj->variation_id)
                                    ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                        $query->orderBy('sort_order', 'asc');
                                    }])->first();

                                if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($decrease_qty)) {

                                    $this->getDecreaseInventory($recipe, $business_id, $location_id, $decrease_qty);
                                }
                                if (! empty($itemObj->modifiers)) {
                                    foreach ($itemObj->modifiers as $key => $modifier) {
                                        $modifierObj = (object) $modifier;
                                        if (! empty($modifierObj->modifier_id) && ! empty($modifierObj->modifier_quantity)) {
                                            TransactionSellLine::where('id', $modifierObj->modifier_sell_line_id)->update(['quantity_returned' => $modifierObj->modifier_quantity]);

                                            $recipeModifier = MfgRecipe::where('variation_id', $modifierObj->modifier_id)
                                                ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                                    $query->orderBy('sort_order', 'asc');
                                                }])->first();
                                            if ($modifierObj->modifier_wastage) {
                                                $decrease_modifiers_inventory_qty = $modifierObj->modifier_quantity_returned;
                                            } else {
                                                $decrease_modifiers_inventory_qty = $modifierObj->modifier_quantity_returned;
                                            }
                                            if (! empty($recipeModifier) && ! empty($business_id) && ! empty($location_id) && ! empty($decrease_modifiers_inventory_qty)) {

                                                $this->getDecreaseInventory($recipeModifier, $business_id, $location_id, $decrease_modifiers_inventory_qty);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                    }
                }
                if (! empty($transaction->final_total)) {
                    $payment[0] = [
                        'amount' => $transaction->final_total,
                        'method' => 'cash',
                    ];
                    //Add payments to Cash Register & Transactions payment
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payment, $business_id, $user_id);

                    $this->cashRegisterUtil->addSellPayments($transaction, $payment, null, $user_id);
                    Transaction::where('id', $transaction_id)->update(['is_suspend' => 0]);
                }
                $output = $this->transactionUtil->returnSale($business_id, $transaction_id, $return_reasom_array);
                if (is_object($output['data'])) {
                    activity()
                        ->causedBy($user)
                        ->performedOn($output['data'])
                        ->useLog('transactions')
                        ->withProperties(['Business_ID' => $business_id])
                        ->log('Api Transaction returned On Hold with inventory.Transaction ID:' . $output['data']->id);

                }
                DB::commit();
                return response()->json(['success' => 1, 'data' => $output], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
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

    /**
     * @param  Request  $request
     *
     * @return array|Application|ResponseFactory|Response
     */
    public function IncreaseInventory($recipe, $business_id, $location_id, $product_quantity)
    {
        //return response()->json(['success' => 1, 'business_id' => $business_id, 'location_id' => $location_id, 'product_quantity' => $product_quantity, 'recipe' => $recipe], 200);
        try {

            if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($product_quantity)) {
                $ingredients = $this->mfgUtil->getIngredientDetails($recipe, $business_id, $location_id);
                //return response()->json(['success' => 1, 'ingredients' => $ingredients], 200);
                foreach ($ingredients as $ingredient) {
                    if ($ingredient['enable_stock']) {
                        $ingredient_quantity = $this->mfgUtil->calc_percentage($ingredient['quantity'], $ingredient['waste_percent'], $ingredient['quantity']);
                        $increase_qty        = $ingredient_quantity * $product_quantity;

                        // return response()->json(['success' => 1, 'product_quantity' => $product_quantity], 200);
                        $response = $this->transactionUtil->increaseQuantity($location_id, $ingredient['product_id'], $ingredient['variation_id'], $increase_qty);
                    }
                }
            }
            return $response ? $response : 'NULL';
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);

        }

    }

    /**
     * @param  Request  $request decrease inventory
     *
     * @return array|Application|ResponseFactory|Response
     */
    public function getDecreaseInventory($recipe, $business_id, $location_id, $product_quantity)
    {
        try {
            if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($product_quantity)) {
                $ingredients = $this->mfgUtil->getIngredientDetails($recipe, $business_id, $location_id);
                foreach ($ingredients as $ingredient) {
                    if ($ingredient['enable_stock']) {
                        $ingredient_quantity = $this->mfgUtil->calc_percentage($ingredient['quantity'], $ingredient['waste_percent'], $ingredient['quantity']);
                        $this->productUtil->decreaseProductQuantity($ingredient['product_id'], $ingredient['variation_id'], $location_id, $ingredient_quantity * $product_quantity);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
        }

    }

    public function deleteSuspendedSales(Request $request)
    {
        try {
            $input = $request->all();
            /* return response()->json(['success' => 1, '  input' => $input], 200); */
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $id          = $input['sell_id'] ?? "";
            $user        = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);
                if (! $user->can('sell.pos.return')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to return POS sell.'], 422);
                }

                if (! empty($id) && ! empty($business_id)) {
                    //Begin transaction
                    DB::beginTransaction();

                    $transaction = Transaction::where('id', $id)
                        ->where('business_id', $business_id)
                        ->where('type', 'sell')
                        ->with(['sell_lines', 'payment_lines'])
                        ->first();
                    if (! empty($transaction->final_total)) {
                        $payment[0] = [
                            'amount' => $transaction->final_total,
                            'method' => 'cash',
                        ];
                        //Add payments to Cash Register & Transactions payment
                        /* $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payment, $business_id, $user_id);
                    $this->cashRegisterUtil->addSellPayments($transaction, $payment, null, $user_id);
                    Transaction::where('id', $id)->update(['is_suspend' => 0]); */
                    }
                    //$output = $this->transactionUtil->deleteSale($business_id, $id);
                    $output = $this->transactionUtil->returnSale($business_id, $id);
                    if (is_object($output['data'])) {
                        activity()
                            ->causedBy($user)
                            ->performedOn($output['data'])
                            ->useLog('transactions')
                            ->withProperties(['Business_ID' => $business_id])
                            ->log('Api Transaction returned On Hold.Transaction ID:' . $output['data']->id);

                    }
                    DB::commit();
                    return response()->json(['success' => 1, 'data' => $output], 200);
                } else {
                    return response(['success' => 0, 'msg' => __('sale.no_transaction_id')], 422);
                }

            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage() . "userID:" . $user_id . "businessID:" . $business_id);
            $msg = $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
        }
    }

    public function deleteAllSuspendedSales(Request $request)
    {
        return response()->json(['success' => 0, 'errors' => 'Unauthorized action'], 422);
        try {
            $input          = $request->all();
            $transactionIds = $input['value'];
            /* return response()->json(['success' => 1, '  input' => $input], 200); */
            $user_id     = $input['user_id'] ?? "";
            $business_id = $input['business_id'] ?? "";
            $user        = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);
                if (! $user->can('sell.pos.return')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to return POS sell.'], 422);

                }
                //Begin transaction
                DB::beginTransaction();
                if (! empty($transactionIds)) {
                    foreach ($transactionIds as $item) {
                        //$output = $this->transactionUtil->deleteSale($business_id, $item);
                        $sell_id = $item['sell_id'] ?? "";
                        $output  = $this->transactionUtil->returnSale($business_id, $sell_id);
                    }
                }
                DB::commit();
                return response()->json(['success' => 1, 'data' => $output], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
        }
    }

    public function recentTransactionDelete(Request $request)
    {
        try {
            $input = $request->all();
            /* return response()->json(['success' => 1, 'input' => $input], 200); */
            $transaction_id        = $request->get('transactionID');
            $return_reason         = $request->get('return_reason');
            $return_payment_method = $request->get('return_payment_method');
            $card_transaction_id   = $request->get('card_transaction_id');
            $return_reason_other   = $request->get('return_reason_other');
            $user_id               = $request->get('user_id');
            $business_id           = $request->get('business_id');
            $user                  = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);
                if (! $user->can('sell.pos.return')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to return POS sell.'], 422);
                }

                $return_reasom_array                          = [];
                $return_reasom_array['return_payment_method'] = $return_payment_method;
                $return_reasom_array['card_transaction_id']   = $card_transaction_id;
                if ($return_reason == 'other') {
                    $return_reasom_array['return_reason'] = $return_reason_other ?? "";
                } else {
                    $return_reasom_array['return_reason'] = $return_reason;
                }
                //Check if the transaction can be edited or not.
                /* $edit_days = $business_details->transaction_edit_days;
                if (!$this->transactionUtil->canBeEdited($transaction_id, $edit_days)) {
                return response([
                'success' => 0, 'msg' => __('messages.transaction_deleted_not_allowed', ['days' => $edit_days]),
                ], 422);
                } */
                //Begin transaction
                DB::beginTransaction();
                //$output = $this->transactionUtil->deleteSale($business_id, $transaction_id, $return_reasom_array);
                $output = $this->transactionUtil->returnSale($business_id, $transaction_id, $return_reasom_array);
                if (is_object($output['data'])) {
                    activity()
                        ->causedBy($user)
                        ->performedOn($output['data'])
                        ->useLog('transactions')
                        ->withProperties(['Business_ID' => $business_id])
                        ->log('Api Transaction returned old.Transaction ID:' . $output['data']->id);

                }

                DB::commit();
                return response()->json(['success' => 1, 'data' => $output], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            $output = ['success' => 0,
                'msg'                => $msg,
                'receipt'            => null,
            ];
            return response()->json($output, 500);
        }
    }

    public function recentTransactionReturn(Request $request)
    {
        try {
            $input                 = $request->all();
            $transaction_id        = $request->get('transactionID');
            $location_id           = $request->get('location_id');
            $return_reason         = $request->get('return_reason');
            $return_payment_method = $request->get('return_payment_method');
            $card_transaction_id   = $request->get('card_transaction_id');
            $return_reason_other   = $request->get('return_reason_other');
            $items                 = $request->get('items') ?? '';
            //return response()->json(['success' => 1, 'items' => $items], 200);
            $user_id     = $request->get('user_id');
            $business_id = $request->get('business_id');
            $user        = User::where('id', $user_id)->with(['media'])->first();
            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                $business_details = $this->businessUtil->getDetails($business_id);
                if (! $user->can('sell.pos.return')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! Not alowed to return POS sell.'], 422);
                }
                $return_reasom_array                          = [];
                $return_reasom_array['return_payment_method'] = $return_payment_method;
                $return_reasom_array['card_transaction_id']   = $card_transaction_id;
                if ($return_reason == 'other') {
                    $return_reasom_array['return_reason'] = $return_reason_other ?? "";
                } else {
                    $return_reasom_array['return_reason'] = $return_reason;
                }
                //Check if the transaction can be edited or not.
                /* $edit_days = $business_details->transaction_edit_days;
                if (!$this->transactionUtil->canBeEdited($transaction_id, $edit_days)) {
                return response([
                'success' => 0, 'msg' => __('messages.transaction_deleted_not_allowed', ['days' => $edit_days]),
                ], 422);
                } */
                //Begin transaction
                DB::beginTransaction();
                if (! empty($items)) {
                    foreach ($items as $item) {
                        $itemObj = (object) $item;
                        //return response()->json(['success' => 1, 'itemObj' => $itemObj], 200);
                        $enable_stock = $itemObj->enable_stock;
                        $wastage      = $itemObj->wastage;

                        if (! empty($itemObj->sell_line_id) && ! empty($itemObj->quantity)) {
                            //return response()->json(['success' => 1, 'if' => 'if here'], 200);
                            TransactionSellLine::where('id', $itemObj->sell_line_id)->update(['quantity_returned' => $itemObj->quantity]);
                            if ($wastage) {
                                $back_inventory_qty = ($itemObj->quantity - $itemObj->quantity_returned);
                            } else {
                                $back_inventory_qty = ($itemObj->quantity);
                            }
                            //return response()->json(['success' => 1, 'back_inventory_qty' => $back_inventory_qty], 200);
                            if ($enable_stock) {
                                //return response()->json(['success' => 1, 'lineproduct_id' => $line->product_id], 200);
                                $this->transactionUtil->increaseQuantity($location_id, $itemObj->product_id, $itemObj->variation_id, $back_inventory_qty);
                            } else {
                                $recipe = MfgRecipe::where('variation_id', $itemObj->variation_id)
                                    ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                        $query->orderBy('sort_order', 'asc');
                                    }])->first();

                                //return response()->json(['success' => 1, 'variation_id' => $itemObj->variation_id, 'location_id' => $location_id, 'quantity' => $back_inventory_qty, 'recipe' => $recipe], 200);
                                if (! empty($recipe) && ! empty($business_id) && ! empty($location_id) && ! empty($back_inventory_qty)) {

                                    $res = $this->IncreaseInventory($recipe, $business_id, $location_id, $back_inventory_qty);
                                    //return response()->json(['success' => 1, 'res' => $res], 200);
                                }
                                if (! empty($itemObj->modifiers)) {
                                    foreach ($itemObj->modifiers as $key => $modifier) {
                                        $modifierObj = (object) $modifier;
                                        // return response()->json(['success' => 1, 'modifierObj' => $modifierObj], 200);
                                        if (! empty($modifierObj->modifier_id) && ! empty($modifierObj->modifier_quantity)) {
                                            TransactionSellLine::where('id', $modifierObj->modifier_sell_line_id)->update(['quantity_returned' => $modifierObj->modifier_quantity]);

                                            $recipeModifier = MfgRecipe::where('variation_id', $modifierObj->modifier_id)
                                                ->with(['variation', 'variation.product', 'variation.product.unit', 'sub_unit', 'ingredients' => function ($query) {
                                                    $query->orderBy('sort_order', 'asc');
                                                }])->first();
                                            if ($modifierObj->modifier_wastage) {
                                                $back_modifiers_inventory_qty = ($modifierObj->modifier_quantity - $modifierObj->modifier_quantity_returned);
                                            } else {
                                                $back_modifiers_inventory_qty = ($modifierObj->modifier_quantity);
                                            }
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
                $output = $this->transactionUtil->returnSale($business_id, $transaction_id, $return_reasom_array);
                if (is_object($output['data'])) {
                    activity()
                        ->causedBy($user)
                        ->performedOn($output['data'])
                        ->useLog('transactions')
                        ->withProperties(['Business_ID' => $business_id])
                        ->log('Api Transaction returned with inventory.Transaction ID:' . $output['data']->id);

                }
                DB::commit();
                return response()->json(['success' => 1, 'data' => $output], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.not_login')], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg    = $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            $output = ['success' => 0, 'msg' => $msg, 'receipt' => null,
            ];
            return response()->json($output, 500);
        }
    }

}
