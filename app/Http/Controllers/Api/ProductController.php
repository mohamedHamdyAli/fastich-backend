<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\ClientCompany;
use App\Http\Controllers\Controller;
use App\OrderTypes;
use App\Product;
use App\TaxRate;
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
use App\Variation;
use App\Warranty;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
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

    /**
     * ProductController constructor.
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
        Util $commonUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->commonUtil = $commonUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * @param Request $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function getLocations(Request $request)
    {
        if (!(auth()->user()->can('sell.pos.add'))) {
            return response(['errors' => 'Unauthorized action'], 422);
        }

        $business_id = request()->session()->get('user.business_id');
        //return response(['userid' => auth()->user()->id], '200');

        //$business_locations = BusinessLocation::forDropdown($business_id, false, true);
        //$bl_attributes = $business_locations['attributes'];
        $selectedBusiness = Business::where('id', $business_id)->first();

        $custom_contact = ClientCompany::where('business_id', $business_id)->get();
        $order_types = OrderTypes::get();

        //$custom_labels = $selectedBusiness->custom_labels ?? "";
        //$custom_labels_decode = json_decode($custom_labels);
        //$custom_contact = $custom_labels_decode->contact ?? "";

        //return response(['business_locations' => $custom_contact], '200');

        $business_name = $selectedBusiness->name ?? "";
        $tax_number_1 = $selectedBusiness->tax_number_1 ?? "";

        /* return response(['business_locations' => $selectedBusiness->custom_labels], '200'); */

        $selectedBusinessLocation = CashRegister::where('business_id', $business_id)->where('user_id',
            auth()->user()->id)->where('status', 'open')->where('closed_at', '=', null)->first();

        if (!empty($selectedBusinessLocation)) {

            $location_id = $selectedBusinessLocation->location_id ?? null;
            //return response(['business_locations' => $selectedBusinessLocation], '200');
            //$query = BusinessLocation::where('business_id', $business_id)->Active();
            $query = BusinessLocation::where('id', $location_id)->Active();
            //return response(['business_locations' => $query], '200');
            //return response(['business_locations' => $selectedBusinessLocation], '200');
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('id', $permitted_locations);
            }

            $query->select(
                \DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
                'id',
                'business_id',
                'receipt_printer_type',
                'number_of_thermal_print_copy',
                'selling_price_group_id',
                'default_payment_accounts'
            );

            $business_locations = $query->get();
            // return response(['business_locations' => $business_locations], '200');
            // echo "here";exit;
            foreach ($business_locations as $location) {

                if (!empty($selectedBusinessLocation) && $selectedBusinessLocation->location_id == $location->id) {
                    $location['selected_business_location'] = 'selected';
                } else {
                    $location['selected_business_location'] = null;
                }
                if (!empty($tax_number_1)) {
                    $location['tax_number_1'] = $tax_number_1;
                }
                if (!empty($tax_number_1)) {
                    $location['business_name'] = $business_name;
                }
                if (!empty($order_types)) {
                    foreach ($order_types as $key => $value) {
                        $order_types[$key]->id = $value->id;
                        $order_type_name = $value->order_name;
                        $order_types[$key]->order_name = trans("receipt." . $order_type_name) ?? $order_type_name;

                    }
                    //return response(['value' => $order_types], '200');
                    $location['order_types'] = $order_types;
                }

                if (!empty($custom_contact)) {
                    $location['custom_contact'] = $custom_contact;
                }

            }

            return response(['business_locations' => $business_locations], '200');

        } else {
            return response(['business_locations' => null], '200');

        }

    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function getCategory(Request $request)
    {
        if (!(auth()->user()->can('sell.pos.add'))) {
            return response(['errors' => 'Unauthorized action'], 422);
        }

        $business_id = request()->session()->get('user.business_id');
        $location_id = $request->get('location_id');

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id, $location_id) : [];

        return response(['categories' => $categories], '200');
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function getProductsOld(Request $request)
    {

        $category_id = $request->get('category_id');
        $brand_id = $request->get('brand_id');
        $location_id = $request->get('location_id');
        $is_favourite = $request->get('is_favourite');
        $term = $request->get('term');

        $check_qty = false;
        $business_id = $request->session()->get('user.business_id');
        $business_location = BusinessLocation::where('id', $location_id)->first();
        $business_details = $this->businessUtil->getDetails($business_id);

        $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
            ->leftjoin('category_locations as cl', 'cl.category_id', '=', 'p.category_id')
            ->leftjoin(
                'variation_location_details AS VLD',
                function ($join) use ($location_id) {
                    $join->on('variations.id', '=', 'VLD.variation_id');

                    //Include Location
                    if (!empty($location_id)) {
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
        //Hide products not available in the selected location
            ->where(function ($q) use ($location_id) {
                $q->where('pl.location_id', $location_id);
            });

        if ($is_favourite == 1) {
            if ($business_location->featured_products != null) {
                $products->whereIn('variations.id', $business_location->featured_products);
            }
        }
        //Include search
        if (!empty($term)) {
            $products->where(function ($query) use ($term) {
                $query->where('p.name', 'like', '%' . $term . '%');
                $query->orWhere('sku', 'like', '%' . $term . '%');
                $query->orWhere('sub_sku', 'like', '%' . $term . '%');
            });
        }

        //Include check for quantity
        if ($check_qty) {
            $products->where('VLD.qty_available', '>', 0);
        }

        if (!empty($category_id) && ($category_id != 'all')) {
            $products->where(function ($query) use ($category_id) {
                $query->where('p.category_id', $category_id);
                $query->orWhere('p.sub_category_id', $category_id);
            });
        }

        if (!empty($brand_id) && ($brand_id != 'all')) {
            $products->where('p.brand_id', $brand_id);
        }

        if (!empty($request->get('is_enabled_stock'))) {
            $is_enabled_stock = 0;
            if ($request->get('is_enabled_stock') == 'product') {
                $is_enabled_stock = 1;
            }

            $products->where('p.enable_stock', $is_enabled_stock);
        }

        if (!empty($request->get('repair_model_id'))) {
            $products->where('p.repair_model_id', $request->get('repair_model_id'));
        }
        $sql = $products->toSql();
        $bindings = $products->getBindings();

        $products = $products->select(
            'p.id as product_id',
            'p.name',
            'variations.sell_price_inc_tax',
            'p.type',
            'p.enable_stock',
            'p.image as product_image',
            'variations.id',
            'variations.name as variation',
            'VLD.qty_available',
            'variations.default_sell_price as selling_price',
            'variations.sub_sku'
        )
            ->with(['media', 'product'])
            ->orderBy("p.sku", 'asc')
            ->get();

        $results = [];

        $products->map(function ($variation) use (&$results, $business_details, $business_id) {
            $product = $variation->product;
            //$product_id = $product->id;

            // Product base Tax applied
            if (!empty($variation->product->tax) && $variation->product->tax_type == 'inclusive') {

                $tax = TaxRate::where('business_id', $business_id)->where('id', $variation->product->tax)->first();
                if (!empty($tax)) {
                    //                    $sellingPriceTax = number_format($variation->selling_price * $tax->amount / 100, 2);
                    //                    $sellingPriceBeforePoint = substr(Str::before($variation->selling_price, '.'), 0, 2);
                    //                    $sellingPriceAfterPoint = substr(Str::after($variation->selling_price, '.'), 0, 2);
                    //                    $sellingPrice = $sellingPriceBeforePoint.'.'.$sellingPriceAfterPoint;
                    //                    $variation->selling_price = $sellingPrice + $sellingPriceTax;

                    /*  echo " tax, <pre> ";
                    print_r($variation); */

                    $variation['product_tax'] = number_format($variation->sell_price_inc_tax * $tax->amount / 100, 2);
                    $sellingPriceTax = number_format($variation->sell_price_inc_tax * $tax->amount / 100, 2);
                    $variation->selling_price = $variation->selling_price;

                    $variation['tax_name'] = $tax->name;
                    $variation['tax_amount'] = $tax->amount;
                    $variation['tax_type'] = $variation->product->tax_type;

                }
            }
            //            if (!empty($business_details->default_sales_tax)){
            //                $tax = TaxRate::find($business_details->default_sales_tax);
            //                $variation->selling_price +=  $variation->selling_price*$tax->amount/100;
            //            }
            $variation->product_image_url = $product->image_url;
            unset($variation->product);

            $results[] = $variation;
        });
        if (!empty($products)) {
            foreach ($products as $key => $pr) {

                $custom_product_price = DB::table('custom_product_price')->where('product_id', $pr->product_id)->where('business_id', $location_id)->first();
                //return response(['pr' => $custom_product_price], '200');
                if (!empty($custom_product_price)) {
                    /*  $products[$key]->selling_price = $custom_product_price->custom_price_exc_tax ?? "";
                    $products[$key]->sell_price_inc_tax = $custom_product_price->custom_price_exc_tax ?? "";
                    $products[$key]->product_tax = (float) $custom_product_price->custom_price_applicable_tax ?? ""; */
                    $products[$key]->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                    $products[$key]->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                    $products[$key]->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    $products[$key]->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    $products[$key]->product_tax = (float) $custom_product_price->custom_price_applicable_tax ?? "";

                }

            }
        }

        /* echo "<pre>";
        print_r($products);
        exit; */

        return response(['products' => $products, 'query' => $sql, 'bindings' => $bindings], '200');
    }

    public function getProducts(Request $request)
    {
        if (!(auth()->user()->can('sell.pos.add'))) {
            return response(['errors' => 'Unauthorized action'], 422);
        }

        try {
            $input = $request->all();
            $category_id = $input['category_id'] ?? "";
            $business_id = $request->session()->get('user.business_id');
            $location_id = $input['location_id'] ?? "";
            $brand_id = $input['brand_id'] ?? "";
            $is_favourite = $input['is_favourite'] ?? "";
            $term = $input['term'] ?? "";
            $is_direct_sell = $input['is_direct_sell'] ?? "";
            $customer_id = $input['customer_id'] ?? "";
            $price_group = $input['price_group'] ?? "";
            $user_id = $input['user_id'] ?? "";
            $user = User::where('id', $user_id)->with(['media'])->first();

            $check_qty = false;
            $business_location = BusinessLocation::where('id', $location_id)->first();
            $business_details = $this->businessUtil->getDetails($business_id);
            $business = Business::where('id', $business_id)->first();
            $common_settings = $business->common_settings ?? "";
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')
                ->leftjoin('category_locations as cl', 'cl.category_id', '=', 'p.category_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
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
            if (!empty($term)) {
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

            if (!empty($category_id) && ($category_id != 'all')) {
                $products->where(function ($query) use ($category_id) {
                    $query->where('p.category_id', $category_id);
                    $query->orWhere('p.sub_category_id', $category_id);
                });
            }

            if (!empty($brand_id) && ($brand_id != 'all')) {
                $products->where('p.brand_id', $brand_id);
            }

            if (!empty($request->get('is_enabled_stock'))) {
                $is_enabled_stock = 0;
                if ($request->get('is_enabled_stock') == 'product') {
                    $is_enabled_stock = 1;
                }

                $products->where('p.enable_stock', $is_enabled_stock);
            }

            if (!empty($request->get('repair_model_id'))) {
                $products->where('p.repair_model_id', $request->get('repair_model_id'));
            }
            $sql = $products->toSql();
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
                $product = $variation->product;
                $variation['quantity'] = 1;
                $variation->product_image_url = $product->image_url;

                /*  Product base Tax applied */
                if (!empty($variation->product->tax) && $variation->product->tax_type == 'inclusive') {
                    $tax = TaxRate::where('business_id', $business_id)->where('id', $variation->product->tax)->first();
                    if (!empty($tax)) {
                        $tax_percent = (($tax->amount + 100) / 100);
                        $tax_percent_multiply = ($tax->amount / 100);
                        $sell_price = ($variation->sell_price_inc_tax / $tax_percent);
                        $product_tax = ($sell_price * $tax_percent_multiply);
                        $variation['product_tax'] = number_format($product_tax, 2, '.', ',');
                        $variation['tax_name'] = $tax->name;
                        $variation['tax_amount'] = $tax->amount;
                        $variation['tax_type'] = $variation->product->tax_type;
                    }
                }
                if (!empty($variation->product)) {
                    $custom_product_price = DB::table('custom_product_price')->where('product_id', $variation->product_id)->where('business_id', $location_id)->first();
                    if (!empty($custom_product_price)) {
                        $variation->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $variation->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $variation->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $variation->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    }

                }

                /*  Get customer group and change the price accordingly */
                $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
                $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
                $variation->default_sell_price = (float) number_format($variation->default_sell_price + ($percent * $variation->default_sell_price / 100), 2);
                $variation->sell_price_inc_tax = (float) $variation->sell_price_inc_tax + ($percent * $variation->sell_price_inc_tax / 100);
                $variation->unit_price = (float) $variation->default_sell_price + ($percent * $variation->default_sell_price / 100);
                $variation->unit_price_inc_tax = (float) $variation->sell_price_inc_tax + ($percent * $variation->sell_price_inc_tax / 100);

                $variation->default_sell_price = number_format($variation->default_sell_price, 2, '.', ',');
                $variation->sell_price_inc_tax = number_format($variation->sell_price_inc_tax, 2, '.', ',');
                $variation->unit_price = number_format($variation->unit_price, 2, '.', ',');
                $variation->unit_price_inc_tax = number_format($variation->unit_price_inc_tax, 2, '.', ',');

                $is_cg = !empty($cg->id) ? true : false;
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($variation, $business_id, $location_id, $is_cg, $is_pg, $variation->variation_id);

                if ($is_direct_sell) {
                    $edit_discount = auth()->user()->can('sell.pos.add_product_discount_from_pos_screen');
                    $edit_price = auth()->user()->can('sell.pos.edit_product_price_from_pos_screen');

                } else {
                    $edit_discount = auth()->user()->can('sell.pos.add_product_discount_from_pos_screen');
                    $edit_price = auth()->user()->can('sell.pos.edit_product_price_from_pos_screen');

                }
                $variation['edit_discount'] = $edit_discount;
                $variation['edit_price'] = $edit_price;

                if ($discount == null) {
                    $discount = [
                        'id' => null,
                        'name' => null,
                        "discount_type" => null,
                        "discount_amount" => null,
                    ];
                }

                /*  Product base Tax applied */
                $tax_dropdown = null;

                if (($variation->tax_id) && ($variation->tax_type == 'inclusive')) {
                    $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $variation->tax_id)->first();
                }
                $allTaxDropdown = TaxRate::where('business_id', $business_id)->get();
                $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);
                $variation['discount'] = $discount;
                $variation['enabled_modules'] = $enabled_modules;
                $variation['tax_dropdown'] = $tax_dropdown;
                $variation['allTaxDropdown'] = $allTaxDropdown;
                $variation['common_settings'] = $common_settings;
                $variation['pos_settings'] = $pos_settings;

                unset($variation->product);
                $results[] = $variation;
            });

            if (!empty($products)) {
                foreach ($products as $key => $pr) {
                    $products[$key]['modifier_products'] = [];
                    $product_id = $pr->product_id ?? "";
                    $custom_product_price = DB::table('custom_product_price')->where('product_id', $product_id)->where('business_id', $location_id)->first();
                    //return response(['pr' => $pr], '200');

                    if (!empty($custom_product_price)) {
                        $products[$key]->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->product_tax = (float) $custom_product_price->custom_price_applicable_tax ?? "";

                    }

                    if ($this->commonUtil->isModuleEnabled('product_module', $business_id) && !$is_direct_sell) {
                        $this_product = Product::where('business_id', $business_id)->find($product_id);
                        if (!empty($this_product)) {
                            if (count($this_product->modifier_sets) > 0) {
                                $product_ms = $this_product->modifier_sets;
                                $products[$key]['modifier_products'] = $product_ms;
                                foreach ($product_ms as $keym => $modifier_set) {
                                    foreach ($modifier_set->variations as $modifier) {
                                        $products[$key]['modifier_products'][$keym]['variations'] = $modifier;
                                        if (!is_null($modifier->tax_id) && $modifier->tax_type == 'inclusive') {
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
            }
            //$output['product_row'] = ['product' => $products, 'query' => $sql, 'bindings' => $bindings];
            //return response(['product' => $output]);
            return response(['success' => 1, 'products' => $products, 'query' => $sql, 'bindings' => $bindings], '200');
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response(['success' => 0, 'msg' => $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage()], 500);
        }
    }

    public function getCategoryProducts(Request $request)
    {
        if (!(auth()->user()->can('sell.pos.add'))) {
            return response(['errors' => 'Unauthorized action'], 422);
        }

        try {
            $input = $request->all();
            $category_id = $input['category_id'] ?? "";
            $business_id = $request->session()->get('user.business_id');
            $location_id = $input['location_id'] ?? "";
            $brand_id = $input['brand_id'] ?? "";
            $is_favourite = $input['is_favourite'] ?? "";
            $term = $input['term'] ?? "";
            $is_direct_sell = $input['is_direct_sell'] ?? "";
            $customer_id = $input['customer_id'] ?? "";
            $price_group = $input['price_group'] ?? "";
            $user_id = $input['user_id'] ?? "";
            $user = User::where('id', $user_id)->with(['media'])->first();

            $check_qty = false;
            $business_location = BusinessLocation::where('id', $location_id)->first();
            $business_details = $this->businessUtil->getDetails($business_id);
            $business = Business::where('id', $business_id)->first();
            $common_settings = $business->common_settings ?? "";
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                ->join('categories AS c', 'p.category_id', '=', 'c.id')
                ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')
                ->leftjoin('category_locations as cl', 'cl.category_id', '=', 'p.category_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
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
            if (!empty($term)) {
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

            if (!empty($category_id) && ($category_id != 'all')) {
                $products->where(function ($query) use ($category_id) {
                    $query->where('p.category_id', $category_id);
                    $query->orWhere('p.sub_category_id', $category_id);
                });
            }

            if (!empty($brand_id) && ($brand_id != 'all')) {
                $products->where('p.brand_id', $brand_id);
            }

            if (!empty($request->get('is_enabled_stock'))) {
                $is_enabled_stock = 0;
                if ($request->get('is_enabled_stock') == 'product') {
                    $is_enabled_stock = 1;
                }

                $products->where('p.enable_stock', $is_enabled_stock);
            }

            if (!empty($request->get('repair_model_id'))) {
                $products->where('p.repair_model_id', $request->get('repair_model_id'));
            }
            $products->groupBy('c.name');
            $products->groupBy('p.name');

            $sql = $products->toSql();
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
                $product = $variation->product;
                $variation['quantity'] = 1;
                $variation->product_image_url = $product->image_url;

                /*  Product base Tax applied */
                if (!empty($variation->product->tax) && $variation->product->tax_type == 'inclusive') {
                    $tax = TaxRate::where('business_id', $business_id)->where('id', $variation->product->tax)->first();
                    if (!empty($tax)) {
                        $tax_percent = (($tax->amount + 100) / 100);
                        $tax_percent_multiply = ($tax->amount / 100);
                        $sell_price = ($variation->sell_price_inc_tax / $tax_percent);
                        $product_tax = ($sell_price * $tax_percent_multiply);
                        $variation['product_tax'] = number_format($product_tax, 2, '.', ',');
                        $variation['tax_name'] = $tax->name;
                        $variation['tax_amount'] = $tax->amount;
                        $variation['tax_type'] = $variation->product->tax_type;
                    }
                }
                if (!empty($variation->product)) {
                    $custom_product_price = DB::table('custom_product_price')->where('product_id', $variation->product_id)->where('business_id', $location_id)->first();
                    if (!empty($custom_product_price)) {
                        $variation->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $variation->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $variation->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $variation->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    }

                }

                /*  Get customer group and change the price accordingly */
                $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
                $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
                $variation->default_sell_price = (float) number_format($variation->default_sell_price + ($percent * $variation->default_sell_price / 100), 2);
                $variation->sell_price_inc_tax = (float) $variation->sell_price_inc_tax + ($percent * $variation->sell_price_inc_tax / 100);
                $variation->unit_price = (float) $variation->default_sell_price + ($percent * $variation->default_sell_price / 100);
                $variation->unit_price_inc_tax = (float) $variation->sell_price_inc_tax + ($percent * $variation->sell_price_inc_tax / 100);

                $variation->default_sell_price = number_format($variation->default_sell_price, 2, '.', ',');
                $variation->sell_price_inc_tax = number_format($variation->sell_price_inc_tax, 2, '.', ',');
                $variation->unit_price = number_format($variation->unit_price, 2, '.', ',');
                $variation->unit_price_inc_tax = number_format($variation->unit_price_inc_tax, 2, '.', ',');

                $is_cg = !empty($cg->id) ? true : false;
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($variation, $business_id, $location_id, $is_cg, $is_pg, $variation->variation_id);
                if ($user) {
                    if ($is_direct_sell) {
                        $edit_discount = auth()->user()->can('sell.pos.add_product_discount_from_pos_screen');
                        $edit_price = auth()->user()->can('sell.pos.edit_product_price_from_pos_screen');
                    } else {
                        $edit_discount = auth()->user()->can('sell.pos.add_product_discount_from_pos_screen');
                        $edit_price = auth()->user()->can('sell.pos.edit_product_price_from_pos_screen');
                    }
                    $variation['edit_discount'] = $edit_discount;
                    $variation['edit_price'] = $edit_price;

                }
                if ($discount == null) {
                    $discount = [
                        'id' => null,
                        'name' => null,
                        "discount_type" => null,
                        "discount_amount" => null,
                    ];
                }

                /*  Product base Tax applied */
                $tax_dropdown = null;

                if (($variation->tax_id) && ($variation->tax_type == 'inclusive')) {
                    $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $variation->tax_id)->first();
                }
                $allTaxDropdown = TaxRate::where('business_id', $business_id)->get();
                $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);
                $variation['discount'] = $discount;
                $variation['enabled_modules'] = $enabled_modules;
                $variation['tax_dropdown'] = $tax_dropdown;
                $variation['allTaxDropdown'] = $allTaxDropdown;
                $variation['common_settings'] = $common_settings;
                $variation['pos_settings'] = $pos_settings;

                unset($variation->product);
                $results[] = $variation;
            });

            if (!empty($products)) {
                foreach ($products as $key => $pr) {
                    $products[$key]['modifier_products'] = [];
                    $product_id = $pr->product_id ?? "";
                    $custom_product_price = DB::table('custom_product_price')->where('product_id', $product_id)->where('business_id', $location_id)->first();
                    //return response(['pr' => $pr], '200');

                    if (!empty($custom_product_price)) {
                        $products[$key]->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->product_tax = (float) $custom_product_price->custom_price_applicable_tax ?? "";

                    }

                    if ($this->commonUtil->isModuleEnabled('product_module', $business_id) && !$is_direct_sell) {
                        $this_product = Product::where('business_id', $business_id)->find($product_id);
                        if (!empty($this_product)) {
                            if (count($this_product->modifier_sets) > 0) {
                                $product_ms = $this_product->modifier_sets;
                                $products[$key]['modifier_products'] = $product_ms;
                                foreach ($product_ms as $keym => $modifier_set) {
                                    foreach ($modifier_set->variations as $modifier) {
                                        $products[$key]['modifier_products'][$keym]['variations'] = $modifier;
                                        if (!is_null($modifier->tax_id) && $modifier->tax_type == 'inclusive') {
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
            }
            return response(['success' => 1, 'products' => $products, 'query' => $sql, 'bindings' => $bindings], '200');
            return response()->json(['success' => 1, 'query' => $sql, 'bindings' => $bindings, 'products' => $products], 200);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response(['success' => 0, 'msg' => $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage()], 500);
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
        $variation_id = $request->get('variation_id');
        $location_id = $request->get('location_id');

        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            $business_id = request()->session()->get('user.business_id');

            $business_details = $this->businessUtil->getDetails($business_id);
            $quantity = request()->get('quantity', 1);

            //Check for weighing scale barcode
            $weighing_barcode = request()->get('weighing_scale_barcode');
            if ($variation_id == 'null' && !empty($weighing_barcode)) {
                $product_details = $this->__parseWeighingBarcode($weighing_barcode);
                if ($product_details['success']) {
                    $variation_id = $product_details['variation_id'];
                    $quantity = $product_details['qty'];
                } else {
                    $output['success'] = false;
                    $output['msg'] = $product_details['msg'];
                    return $output;
                }
            }

            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;
            $product = $this->productUtil->getDetailsFromProductVariation($variation_id, $business_id, $location_id, $check_qty);
            //return response()->json(['product' => $product], 200);

            if (!isset($product->quantity_ordered)) {
                $product->quantity_ordered = $quantity;
            }

            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);

            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            // Product base Tax applied
            $tax_dropdown = null;
            if (!empty($product->tax_id) && $product->tax_type == 'inclusive') {
                $tax_dropdown = TaxRate::where('business_id', $business_id)->where('id', $product->tax_id)->first();
            }
            // $tax_dropdown = null;
            //            if (!empty($business_details->default_sales_tax)){
            //                $tax_dropdown =  TaxRate::where('business_id', $business_id)->where('id', $business_details->default_sales_tax)->first();
            //            }

            $allTaxDropdown = TaxRate::where('business_id', $business_id)->get();

            $enabled_modules = $this->transactionUtil->allModulesEnabled($business_id);

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;
            $product->item_tax = 0.00;

            $purchase_line_id = request()->get('purchase_line_id');

            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            $warranties = $this->__getwarranties();

            $enabledTax = false;
            if ($request->session()->get('business.enable_inline_tax') == 1) {
                $enabledTax = true;
            }
            $common_settings = session()->get('business.common_settings');
            $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;

            $output['success'] = true;

            $waiters = [];
            if ($this->commonUtil->isModuleEnabled('service_staff_module', $business_id) && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }
            $product['quantity'] = $quantity;
            $product['unit_price'] = $product->default_sell_price;
            $product['unit_price_inc_tax'] = $product->sell_price_inc_tax;

            if (!empty($product)) {
                $custom_product_price = DB::table('custom_product_price')->where('product_id', $product->product_id)->where('business_id', $location_id)->first();
                if (!empty($custom_product_price)) {
                    $product->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                    $product->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                    $product->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                    $product->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                }

            }

            if (request()->get('type') == 'sell-return') {
                $output['product_row'] = ['product' => $product, 'row_count' => $row_count, 'tax_dropdown' => $tax_dropdown, 'all_tax_dropdown' => $allTaxDropdown, 'enabled_modules' => $enabled_modules, 'enabled_tax' => $enabledTax, 'enabled_warranties' => $is_warranty_enabled, 'sub_units' => $sub_units];
            } else {
                $is_cg = !empty($cg->id) ? true : false;
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg, $variation_id);

                if ($is_direct_sell) {
                    $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
                    $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');
                } else {
                    $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
                    $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
                }
                if ($discount == null) {
                    $discount = [
                        'id' => null,
                        'name' => null,
                        "discount_type" => null,
                        "discount_amount" => null,
                    ];
                }
                $output['product_row'] = ['product' => $product, 'row_count' => $row_count, 'tax_dropdown' => $tax_dropdown, 'all_tax_dropdown' => $allTaxDropdown, 'enabled_modules' => $enabled_modules, 'pos_settings' => $pos_settings, 'sub_units' => $sub_units, 'discount' => $discount, 'waiters' => $waiters, 'enabled_tax' => $enabledTax, 'enabled_warranties' => $is_warranty_enabled, 'edit_discount' => $edit_discount, 'edit_price' => $edit_price, 'purchase_line_id' => $purchase_line_id, 'warranties' => $warranties, 'quantity' => $quantity];
            }

            $output['enable_sr_no'] = $product->enable_sr_no;

            $output['modifier_products'] = [];
            if ($this->commonUtil->isModuleEnabled('product_module', $business_id) && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['modifier_products'] = $product_ms;
                    foreach ($product_ms as $key => $modifier_set) {
                        foreach ($modifier_set->variations as $modifier) {
                            $modifier->item_tax = ($modifier->sell_price_inc_tax - $modifier->default_sell_price);
                            $output['modifier_products'][$key]['variations'] = $modifier;
                            if (!is_null($modifier->tax_id) && $modifier->tax_type == 'inclusive') {
                                $output['modifier_products'][$key]['variations']['tax_dropdown'] = TaxRate::where('id', $modifier->tax_id)->first();
                            } else {
                                $output['modifier_products'][$key]['variations']['tax_dropdown'] = null;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = [];
            $output['msg'] = __('lang_v1.item_out_of_stock');
        }

        return response(['product' => $output]);
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function checkInternetConnection()
    {
        //checking connection with @fopen
        if (@fopen("https://tek-store.net", "r")) {
            $msg = true; // You are connected to the internet.
        } else {
            $msg = false; // You seem to be offline. Please check your internet connection.
        }

        return response(['data' => $msg]);
    }

    private function __getwarranties()
    {
        $business_id = session()->get('user.business_id');
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::where('business_id', $business_id)
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

            if (!empty($result)) {
                return ['variation_id' => $result->variation_id,
                    'qty' => $qty,
                    'success' => true,
                ];
            } else {
                $error_msg = trans("lang_v1.sku_not_match", ['sku' => $sku]);
            }
        } else {
            $error_msg = trans("lang_v1.prefix_did_not_match");
        }

        return [
            'success' => false,
            'msg' => $error_msg,
        ];
    }
}