<?php

namespace Modules\Deliverect\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Modules\Deliverect\App\Models\DeliverectRegistration;

use App\Http\Controllers\Controller;
use App\BusinessLocation;
use App\Utils\ContactUtil;
use App\Product;
use App\Category;
use App\Utils\ProductUtil;
use App\TaxRate;
use App\Business;
use App\Variation;
use App\Utils\Util;
use App\Utils\BusinessUtil;


class FastichProductService
{
    protected $deliverectService;
    protected $productPosService;
    protected $productUtil;
    protected $contactUtil;
    protected $commonUtil;
    protected $businessUtil;
    protected $channelService;
    protected $productPOSService;

    public function __construct(
        DeliverectService $deliverectService,
        ProductPOS $productPosService,
        ProductUtil $productUtil,
        ContactUtil $contactUtil,
        BusinessUtil $businessUtil,
        Util $commonUtil,
        DeliverectChannelService $channelService,
        ProductPOS $productPOSService
    ) {
        $this->deliverectService = $deliverectService;
        $this->productPosService = $productPosService;
        $this->productUtil = $productUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->commonUtil = $commonUtil;
        $this->channelService = $channelService;
        $this->productPOSService = $productPOSService;
    }

    public function getCategories($business_id, $location_id)
    {
        try {
            $business = Business::where('id', $business_id)->first();
            if (($business)) {
                //If brands, category are enabled then send else false.
                $categories = ($business->enable_category == 1) ? Category::catAndSubCategories($business_id, $location_id) : [];
                if (($categories)) {
                    return [
                        "success" => true,
                        "message" => "Categories fetched successfully",
                        "categories" => $categories,
                    ];
                } else {
                    return [
                        "success" => false,
                        "message" => "No categories Found for this business",
                        "categories" => [],
                    ];
                }
            } else {
                return [
                    "success" => false,
                    "message" => "No business Found",
                    "categories" => [],
                ];
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return [
                "success" => false,
                "message" => __('messages.something_went_wrong'),
                "categories" => [],
            ];
        }
    }


    public function mapFastichProducsToDeliverect($fasticProducts, $channels, $business_id, $location_id)
    {
        $mappedProducts = [];
        $modifierSets = [];
        $modifierProducts = [];



        foreach ($fasticProducts as $product) {
            $price = (float)number_format((float)$product["unit_price_inc_tax"], 2, '.', '');
            if ($product["discount"] != null) {
                $discountType = $product["discount"]["discount_type"];
                $discountAmount = (float) $product["discount"]["discount_amount"];
                // apply the discount
                if ($discountType == 'fixed') {
                    $price = $price - $discountAmount;
                } else {
                    $price = $price - ($price * $discountAmount / 100);
                }
                $price = $price * 100;
            } else {
                $price = $price * 100;
            }
            $priceLevels = $this->productUtil->get_product_price_levels(
                $business_id,
                $location_id,
                $product["id"],
                $price / 100,
            );
            Log::info("Price Levels for " . $product["id"], $priceLevels);


            $priceLevels = array_map(function ($price) {
                return $price * 100;
            }, (array)$priceLevels);




            $subModifierSets = [];
            // loop the modifier_products
            $modifiersSetArray = $product["modifier_products"];
            // if the type of modifiersSetArray is not Array call the ->toArray()
            if (!is_array($modifiersSetArray)) {
                $modifiersSetArray = $modifiersSetArray->toArray();
            }
            foreach ($modifiersSetArray as $modifierSet) {
                // if modifierset does not contain a item with same posProductId
                $modifierVariations = $modifierSet["variations"];
                if (!in_array((string)$modifierSet["id"], array_column($modifierSets, "posProductId"))) {
                    $subProducts = [];

                    foreach ($modifierSet["variations"] as $modifier) {
                        $variationPriceLevels = [];
                        $variationPrice = (float)number_format((float)$modifier["sell_price_inc_tax"], 2, '.', '') * 100;
                        foreach ($channels as $channel) {
                            $variationPriceLevels[$channel['channel_name']] = $variationPrice;
                        }
                        $modifierTaxRate = null;
                        if (!empty($modifier["tax_dropdown"])) {
                            $modifierTaxRate = $modifier["tax_dropdown"]["amount"] * 1000;
                        }
                        $modifierProducts[] = [
                            "productType" => 2,
                            "plu" => $modifier["sub_sku"],
                            "price" => $variationPrice,
                            "priceLevels" => $variationPriceLevels,
                            "name" => $modifier["name"],
                            "posProductId" => (string)$modifier["id"],
                            "productTags" => [],
                            "imageUrl" => "",
                            "description" => "",
                            "deliveryTax" => $modifierTaxRate,
                            "takeawayTax" => $modifierTaxRate,
                            "eatInTax" => $modifierTaxRate,
                        ];

                        $subProducts[] = $modifier["sub_sku"];
                    }

                    $modifierSets[] = [
                        "productType" => 3,
                        "plu" => $modifierSet["sku"],
                        "name" => $modifierSet["name"],
                        "posProductId" => (string)$modifierSet["id"],
                        "imageUrl" => "",
                        "description" => $modifierSet["product_description"] ?? "",
                        "subProducts" => $subProducts
                    ];
                }
                $subModifierSets[] = (string)$modifierSet["sku"];
            }

            $taxRate = null;
            if (!empty($product["tax_amount"])) {
                $taxRate = $product["tax_amount"] * 1000;
            }

            $mappedProducts[] = [
                "productType" => 1,
                "name" => $product["name"],
                "price" => $price,
                "priceLevels" => $priceLevels,
                "plu" => $product["sku"],
                "description" => $product["product_description"] ?? "",
                "posProductId" => (string)$product["id"],
                "posCategoryIds" => (string)($product["category_id"] ?? ""),
                "imageUrl" => $product["product_image_url"],
                "productTags" => [],
                "subProducts" => $subModifierSets,
                "deliveryTax" => $taxRate,
                "takeawayTax" => $taxRate,
                "eatInTax" => $taxRate,
            ];
        }
        $mappedProducts = array_merge($mappedProducts, $modifierSets, $modifierProducts);
        return $mappedProducts;
    }


    public function getProducts($business_id, $pos_location, $filters = [])
    {
        try {
            $product_ids = $filters['product_ids'] ?? [];
            $product_sku = $filters['product_sku'] ?? [];
            $category_id = "";
            $business_id = $business_id;
            $location_id = $pos_location;
            $brand_id = "";
            $is_favourite = "";
            $term = "";
            $is_direct_sell = "";
            $customer_id = "";
            $price_group = "";


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

            if (!empty($product_ids)) {
                $products->whereIn('p.id', $product_ids);
            }

            if (!empty($product_sku)) {
                $products->whereIn('p.sku', $product_sku);
            }

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


            $sql = $products->toSql();
            $bindings = $products->getBindings();

            $products = $products->select(
                'p.id as product_id',
                'p.id',
                'p.product_description',
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

            $products->map(function ($variation) use (&$results, $business_details, $business_id, $common_settings, $pos_settings, $location_id, $customer_id, $is_direct_sell) {
                $product = $variation->product;
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
                    if (!empty($custom_product_price)) {
                        $products[$key]->default_sell_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->unit_price = (float) $custom_product_price->custom_price_exc_tax ?? "";
                        $products[$key]->sell_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->unit_price_inc_tax = (float) $custom_product_price->custom_price ?? "";
                        $products[$key]->product_tax = (float) $custom_product_price->custom_price_applicable_tax ?? "";
                    }

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
            return ['success' => 1, 'products' => $products->toArray(), "status" => 200];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = "File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage();
            return ['success' => 0, 'msg' => $msg,  "status" => 500];
        }
    }

    private function unitPriceExecTax($deliverectPrice, $deliverectTaxRate)
    {
        if ($deliverectTaxRate == 0 || $deliverectTaxRate == null) {
            return $deliverectPrice;
        }
        $price = $deliverectPrice / 100;
        $taxRate = $deliverectTaxRate / 1000;
        $tax = ($price / (100 + $taxRate)) * 100;
        return round($tax, 2) * 100;
    }

    private function taxAmount($deliverectPrice, $deliverectTaxRate)
    {
        $priceExcludingTax = $this->unitPriceExecTax($deliverectPrice, $deliverectTaxRate);
        return $deliverectPrice - $priceExcludingTax;   
    }


    function parseProductsForOrderFromDeliverect($items, $fastichProducts)
    {

        // $orderedProductsSkus = array_column($items, 'plu');
        // $this->getProducts($business_id, $pos_location, [
        //     "product_sku" => $orderedProductsSkus
        // ]);
        $products = [];


        foreach ($items as $item) {

            // get the respective product from fastich
            $fastichProduct = $fastichProducts[array_search($item['plu'], array_column($fastichProducts, 'sku'))];
            $modifiers = [];
            foreach ($item['subItems'] as $subItem) {
                $modifierSet = $fastichProduct['modifier_products']->toArray();
                // split the plu by - and take the first part
                $modifierSetPlu = explode("-", $subItem['plu'])[0];
                $fastichModifierSet = $modifierSet[array_search($modifierSetPlu, array_column($modifierSet, 'sku'))];
                $modifier = $fastichModifierSet['variations'][array_search($subItem['plu'], array_column($fastichModifierSet['variations'], 'sub_sku'))];
                $modifiers[] = $modifier;
            }
            $modifiers_list = [];
            $modifierIds = [];
            $modifierSetId = [];
            $modifierPrice = [];
            $modifierPriceIncTax = [];
            $modifierQuantity = [];
            $modifierTaxId = [];

            $totalTaxPerUnit = 0;
            $modifierTaxPerUnit = 0;
            $modifierSum = 0;


            foreach ($modifiers as $modifier) {
                $modifiers_list[] = [
                    "name" => $modifier['name'],
                    "price" => (float)$modifier['sell_price_inc_tax'],
                ];
                $modifierIds[] = $modifier['id'];
                $modifierSetId[] = $fastichModifierSet['id'];
                $modifierPrice[] = $modifier['default_sell_price'];
                $modifierPriceIncTax[] = $modifier['sell_price_inc_tax'];
                $modifierQuantity[] = $item['quantity'];
                $modifierTaxId[] = $modifier['tax_id'];
                $modifierTaxPerUnit += (float)$modifier['sell_price_inc_tax'] - (float)$modifier['default_sell_price'];
                $modifierSum += (float)$modifier['sell_price_inc_tax'];
            }

            $modifierTaxPerUnit = round($modifierTaxPerUnit, 2);

            $deliverectPrice = $item['price'];
            $taxRate = ($fastichProduct['tax_amount'] ?? 0) * 1000;

            $unitPriceInclusiveTax = $deliverectPrice / 100;
            $unitPrice = $this->unitPriceExecTax($deliverectPrice, $taxRate) / 100;
            $unitPrice = round($unitPrice, 2);
            $itemTax = round($unitPriceInclusiveTax - $unitPrice, 2);
            $totalTaxPerUnit += ($itemTax + $modifierTaxPerUnit);

            /* Log::info("Deliverect Price: " . $deliverectPrice);
            Log::info("Deliverect Tax Rate: " . $taxRate);
            Log::info("Unit Price Inclusive Tax: " . $unitPriceInclusiveTax);
            Log::info("Unit Price: " . $unitPrice);
            Log::info("Item Tax: " . $itemTax);
            Log::info("Total Tax Per Unit: " . $totalTaxPerUnit); */

            $discountType = $fastichProduct['discount']['discount_type'] ?? null;
            $discountAmount = $fastichProduct['discount']['discount_amount'] ?? null;

            $totalAmountOfProduct = $item['quantity'] * ($unitPriceInclusiveTax + $modifierSum);
            $totalTaxOfProduct = $totalTaxPerUnit * $item['quantity'];

           /*  Log::info("Total Amount of Product: " . $totalAmountOfProduct);
            Log::info("Total Tax of Product: " . $totalTaxOfProduct); */

            $products[] = [
                'name' => $fastichProduct['name'],
                'image_url' => $fastichProduct['product_image_url'],
                'quantity' => $item['quantity'],
                'remark' => $item['remark'] ?? "",
                'product_type' => "includes",
                'unit_price' => round($unitPrice, 2),
                'line_discount_type' => $discountType,
                'line_discount_amount' => $discountAmount,
                'item_tax' => round($itemTax, 2),
                'tax_id' => $fastichProduct['tax_id'],
                'sell_line_note' => null,
                'lot_no_line_id' => null,
                'product_id' => $fastichProduct['id'],
                'variation_id' => $fastichProduct['variation_id'],
                'enable_stock' => $fastichProduct['enable_stock'],
                'product_unit_id' => $fastichProduct['unit_id'],
                'base_unit_multiplier' => 1,
                'unit_price_exc_tax' => round($unitPrice, 2),
                'unit_price_inc_tax' => $unitPriceInclusiveTax,
                'modifiers_list' => $modifiers_list,
                'modifier' => $modifierIds,
                'modifier_price' => $modifierPrice,
                'modifier_quantity' => $modifierQuantity,
                'modifier_set_id' => $modifierSetId,
                'modifier_price_inc_tax' => $modifierPriceIncTax,
                'modifier_tax_id' => $modifierTaxId,
                'sell_price_inc_tax' => $unitPriceInclusiveTax,
                "total_amount" => $totalAmountOfProduct,
                "total_tax" => $totalTaxOfProduct,
                "tax_per_unit" => $totalTaxPerUnit,
                "modifier_tax_per_unit" => $modifierTaxPerUnit,
            ];
        }
        return $products;
    }


    public function syncProducts($business_id, $pos_location, $selectedProductsIds = [])
    {
        $response = $this->getProducts($business_id, $pos_location, [
            "product_ids" => $selectedProductsIds
        ]);

        if ($response['success'] == 0) {
            return $response;
        }

        if (count($response['products']) == 0) {
            return [
                "success" => 0,
                "msg" => "No products found",
                "status" => 404
            ];
        }

        $products = $response['products'];

        $result = $this->channelService->getBusinessChannels($business_id);
        if (!$result['success']) {
            return $result;
        }

        $channels = $result['data'];

        $priceLevels = [];
        foreach ($channels as $channel) {
            $priceLevels[] = [
                "name" => $channel['channel_name'],
                "posId" => $channel['channel_name']
            ];
        }
        $mappedProducts = $this->mapFastichProducsToDeliverect($products, $channels, $business_id, $pos_location);
        Log::info("Mapped Prod: ", $mappedProducts[0]);

        $res = $this->getCategories($business_id, $pos_location);
        if (!$res['success']){
            Log::info("Error: ", $res);
            return $res;
        }
        $categories = [];
        foreach($res["categories"] as $cat){
            $categories[] = [
                "name" => $cat['name'],
                "posCategoryId"=> (string)$cat['id'],
                "imageUrl" => $cat["image_url"]
            ];
        }

        Log::info("Syncing Categories: ", $categories);

        $res = $this->productPOSService->sync_products([
            "products" => $mappedProducts,
            "priceLevels" => $priceLevels,
            "categories" => $categories,
        ], $business_id, $pos_location,);
        return $res;
    }
}
