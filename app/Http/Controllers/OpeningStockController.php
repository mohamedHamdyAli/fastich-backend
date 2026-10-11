<?php
namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpeningStockController extends Controller
{

    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil     = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addold($product_id)
    {
        if (! auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Get the product
        $product = Product::where('business_id', $business_id)
            ->where('id', $product_id)
            ->with(['variations',
                'variations.product_variation',
                'unit',
                'product_locations',
            ])
            ->first();
        if (! empty($product) && $product->enable_stock == 1) {
            //Get Opening Stock Transactions for the product if exists
            $transactions = Transaction::where('business_id', $business_id)
                ->where('opening_stock_product_id', $product_id)
                ->where('type', 'opening_stock')
                ->with(['purchase_lines'])
                ->get();
            //return response(['transactions' => $transactions], 200);

            $purchases = [];
            foreach ($transactions as $transaction) {
                $purchase_lines = [];

                foreach ($transaction->purchase_lines as $purchase_line) {
                    if (! empty($purchase_lines[$purchase_line->variation_id])) {
                        $k = count($purchase_lines[$purchase_line->variation_id]);
                    } else {
                        $k                                            = 0;
                        $purchase_lines[$purchase_line->variation_id] = [];
                    }

                    //Show only remaining quantity for editing opening stock.
                    $purchase_lines[$purchase_line->variation_id][$k]['quantity']         = $purchase_line->quantity_remaining;
                    $purchase_lines[$purchase_line->variation_id][$k]['purchase_price']   = $purchase_line->purchase_price;
                    $purchase_lines[$purchase_line->variation_id][$k]['purchase_line_id'] = $purchase_line->id;
                    $purchase_lines[$purchase_line->variation_id][$k]['exp_date']         = $purchase_line->exp_date;
                    $purchase_lines[$purchase_line->variation_id][$k]['lot_number']       = $purchase_line->lot_number;
                }
                $purchases[$transaction->location_id] = $purchase_lines;
            }

            $locations = BusinessLocation::forDropdown($business_id);

            //Unset locations where product is not available
            $available_locations = $product->product_locations->pluck('id')->toArray();
            foreach ($locations as $key => $value) {
                if (! in_array($key, $available_locations)) {
                    unset($locations[$key]);
                }
            }

            $enable_expiry = request()->session()->get('business.enable_product_expiry');
            $enable_lot    = request()->session()->get('business.enable_lot_number');

            if (request()->ajax()) {
                return view('opening_stock.ajax_add_old')
                    ->with(compact(
                        'product',
                        'locations',
                        'purchases',
                        'enable_expiry',
                        'enable_lot'
                    ));
            }

            return view('opening_stock.add_old')
                ->with(compact(
                    'product',
                    'locations',
                    'purchases',
                    'enable_expiry',
                    'enable_lot'
                ));
        }
    }
    public function add($product_id)
    {
        if (! auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Get the product
        $product = Product::where('business_id', $business_id)
            ->where('id', $product_id)
            ->with(['variations',
                'variations.product_variation',
                'unit',
                'product_locations',
            ])
            ->first();

        //return response(['product' => $variation_id], 200);

        if (! empty($product) && $product->enable_stock == 1) {
            $locations = BusinessLocation::forDropdown($business_id);
            //Unset locations where product is not available
            $available_locations = $product->product_locations->pluck('id')->toArray();
            foreach ($locations as $key => $value) {
                if (! in_array($key, $available_locations)) {
                    unset($locations[$key]);
                }
            }

            //return response(['locations' => $locations], 200);

            //Get Opening Stock Transactions for the product if exists
            $query = Transaction::leftjoin('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')
                ->join('products as p', 'p.id', '=', 'pl.product_id')
                ->join('variations as v', 'v.product_id', '=', 'p.id')
                ->join('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->join('units as u', 'u.id', '=', 'p.unit_id')
                ->where('transactions.business_id', $business_id)
            //->where('transactions.opening_stock_product_id', $product_id)
                ->where('pl.product_id', $product_id)
            //->where('transactions.type', 'opening_stock')
                ->whereIn('transactions.type', ['opening_stock', 'purchase'])
            //->with(['purchase_lines'])
                ->select(
                    'transactions.id as tID',
                    'p.id as product_id',
                    'v.id as variation_id',
                    'v.default_purchase_price',
                    'p.name',
                    'p.type',
                    'bl.id as location_id',
                    'bl.name as location_name',
                    'u.short_name',
                    'pl.purchase_price',
                    'pl.exp_date',
                    'pl.lot_number',
                    DB::raw('SUM( pl.quantity)  as quantity'),
                    DB::raw('SUM( pl.quantity - (pl.quantity_sold + pl.quantity_adjusted) )  as quantity_remaining'),
                    DB::raw('SUM(pl.quantity_sold ) as quantity_sold'),
                    DB::raw('SUM(  pl.quantity_adjusted ) as quantity_adjusted'),
                )
                ->groupBy('transactions.location_id');

            $transactions = $query->get();
            $sql          = $query->toSql();
            $bindings     = $query->getBindings();

            //return response(['tosql' => $sql, 'bindings' => $bindings], 200);

            //return response(['transactions' => $transactions], 200);
            $stocks = [];
            if (! empty($locations)) {
                foreach ($locations as $key => $location) {
                    $index        = $key;
                    $variation_id = $product->variations[0]->id ?? $key;

                    $stocks[$index]['stock']['location_id']   = $key;
                    $stocks[$index]['stock']['product_id']    = $product_id;
                    $stocks[$index]['stock']['variation_id']  = $variation_id;
                    $stocks[$index]['stock']['location_name'] = $location;
                    $stocks[$index]['stock']['short_name']    = $product->unit->short_name;
                    $stocks[$index]['stock']['product_name']  = $product->name;
                    $stocks[$index]['stock']['type']          = $product->type;

                    if (! empty($transactions)) {
                        foreach ($transactions as $subKey => $transaction) {
                            //$index = $key;
                            if ($transaction->purchase_price == 0) {
                                $transaction->purchase_price = $transaction->default_purchase_price;
                            }
                            if ($key == $transaction->location_id) {
                                $stocks[$index]['stock']['tID']                = $transaction->tID;
                                $stocks[$index]['stock']['quantity_remaining'] = $transaction->quantity_remaining;
                                $stocks[$index]['stock']['quantity_sold']      = $transaction->quantity_sold;
                                $stocks[$index]['stock']['quantity_adjusted']  = $transaction->quantity_adjusted;
                                $stocks[$index]['stock']['purchase_price']     = $transaction->purchase_price;
                            } else {
                                $stocks[$index]['stock']['purchase_price'] = $transaction->default_purchase_price;
                            }
                        }
                    }
                }
            }
            //return response([$stocks], 200);
            $purchases = [];
            foreach ($transactions as $transaction) {
                $purchase_lines = [];
                foreach ($transaction->purchase_lines as $purchase_line) {
                    if (! empty($purchase_lines[$purchase_line->variation_id])) {
                        $k = count($purchase_lines[$purchase_line->variation_id]);
                    } else {
                        $k                                            = 0;
                        $purchase_lines[$purchase_line->variation_id] = [];
                    }
                    //Show only remaining quantity for editing opening stock.
                    $purchase_lines[$purchase_line->variation_id][$k]['quantity']         = $purchase_line->quantity_remaining;
                    $purchase_lines[$purchase_line->variation_id][$k]['purchase_price']   = $purchase_line->purchase_price;
                    $purchase_lines[$purchase_line->variation_id][$k]['purchase_line_id'] = $purchase_line->id;
                    $purchase_lines[$purchase_line->variation_id][$k]['exp_date']         = $purchase_line->exp_date;
                    $purchase_lines[$purchase_line->variation_id][$k]['lot_number']       = $purchase_line->lot_number;
                }
                $purchases[$transaction->location_id] = $purchase_lines;
            }
            //Unset locations where product is not available
            $available_locations = $product->product_locations->pluck('id')->toArray();
            foreach ($locations as $key => $value) {
                if (! in_array($key, $available_locations)) {
                    unset($locations[$key]);
                }
            }
            $enable_expiry = request()->session()->get('business.enable_product_expiry');
            $enable_lot    = request()->session()->get('business.enable_lot_number');
            if (request()->ajax()) {
                return view('opening_stock.ajax_add')
                    ->with(compact(
                        'product',
                        'locations',
                        'transactions',
                        'stocks',
                        'purchases',
                        'enable_expiry',
                        'enable_lot'
                    ));
            }
            return view('opening_stock.add')
                ->with(compact(
                    'product',
                    'transactions',
                    'locations',
                    'stocks',
                    'purchases',
                    'enable_expiry',
                    'enable_lot'
                ));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        if (! auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $opening_stocks     = $request->input('stocks');
            $quantity_remaining = $request->input('quantity_remaining');
            $product_id         = $request->input('product_id');
            $business_id        = $request->session()->get('user.business_id');
            $user_id            = $request->session()->get('user.id');
            $product            = Product::where('business_id', $business_id)
                ->where('id', $product_id)
                ->with(['variations', 'product_tax'])
                ->first();
            $locations = BusinessLocation::forDropdown($business_id)->toArray();
            if (! empty($product) && $product->enable_stock == 1) {
                //Get product tax
                $tax_percent = ! empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
                $tax_id      = ! empty($product->product_tax->id) ? $product->product_tax->id : null;
                //Get start date for financial year.
                // $transaction_date = request()->session()->get("financial_year.start");
                //$transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
                $transaction_date = \Carbon::now();

                DB::beginTransaction();
                //$key is the location_id
                //return response(['opening_stocks' => $opening_stocks], 200);
                if ($opening_stocks) {
                    foreach ($opening_stocks as $key_os => $value) {
                        $location_id    = $key_os;
                        $purchase_total = 0;
                        //Check if valid location
                        if (array_key_exists($location_id, $locations)) {
                            $purchase_lines            = [];
                            $updated_purchase_line_ids = [];
                            //create purchase_lines array
                            //$k is the variation id
                            //return response(['value' => $value], 200);
                            foreach ($value as $k => $rows) {
                                //echo $k;exit;
                                //return response(['rows' => $rows], 200);
                                foreach ($rows as $key => $v) {
                                    $purchase_price         = $this->productUtil->num_uf(trim($v['purchase_price']));
                                    $item_tax               = $this->productUtil->calc_percentage($purchase_price, $tax_percent);
                                    $purchase_price_inc_tax = $purchase_price + $item_tax;
                                    $qty_remaining          = $this->productUtil->num_uf(trim($v['quantity']));
                                    $location_name          = $v['location_name'] ?? null;
                                    $quantity_remaining     = $this->productUtil->num_uf(trim($quantity_remaining));
                                    $exp_date               = null;
                                    if (! empty($v['exp_date'])) {
                                        $exp_date = $this->productUtil->uf_date($v['exp_date']);
                                    }
                                    $lot_number = null;
                                    if (! empty($v['lot_number'])) {
                                        $lot_number = $v['lot_number'];
                                    }
                                    $purchase_line = null;
                                    if ($qty_remaining > 0) {
                                        //create newly added purchase lines
                                        $purchase_line               = new PurchaseLine();
                                        $purchase_line->product_id   = $product->id;
                                        $purchase_line->variation_id = $k;
                                        $this->productUtil->updateProductQuantity($location_id, $product->id, $k, $qty_remaining, 0, null, false);
                                        //Calculate transaction total
                                        $purchase_total += ($purchase_price_inc_tax * $qty_remaining);

                                    } else {
                                        $output = ['success' => 0, 'msg' => __('lang_v1.quantity_is_required') . ' ' . $location_name];
                                        if (request()->ajax()) {
                                            return $output;
                                        }
                                        return back()->with('status', $output);
                                    }

                                    if (! is_null($purchase_line)) {
                                        $purchase_line->item_tax               = $item_tax;
                                        $purchase_line->tax_id                 = $tax_id;
                                        $purchase_line->quantity               = $qty_remaining;
                                        $purchase_line->pp_without_discount    = $purchase_price;
                                        $purchase_line->purchase_price         = $purchase_price;
                                        $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;
                                        $purchase_line->exp_date               = $exp_date;
                                        $purchase_line->lot_number             = $lot_number;
                                        $purchase_lines[]                      = $purchase_line;
                                    }
                                }
                                //create transaction & purchase lines
                                if (! empty($purchase_lines)) {
                                    $is_new_transaction = true;
                                    $transaction        = Transaction::create(
                                        [
                                            'type'                     => 'opening_stock',
                                            'opening_stock_product_id' => $product->id,
                                            'status'                   => 'received',
                                            'business_id'              => $business_id,
                                            'transaction_date'         => $transaction_date,
                                            'total_before_tax'         => $purchase_total,
                                            'location_id'              => $location_id,
                                            'final_total'              => $purchase_total,
                                            'payment_status'           => 'paid',
                                            'created_by'               => $user_id,
                                        ]
                                    );
                                    //unset deleted purchase lines
                                    $delete_purchase_line_ids = [];
                                    $delete_purchase_lines    = null;
                                    $delete_purchase_lines    = PurchaseLine::where('transaction_id', $transaction->id)
                                        ->whereNotIn('id', $updated_purchase_line_ids)
                                        ->get();

                                    if ($delete_purchase_lines->count()) {
                                        foreach ($delete_purchase_lines as $delete_purchase_line) {
                                            $delete_purchase_line_ids[] = $delete_purchase_line->id;

                                            //decrease deleted only if previous status was received
                                            $this->productUtil->decreaseProductQuantity(
                                                $delete_purchase_line->product_id,
                                                $delete_purchase_line->variation_id,
                                                $transaction->location_id,
                                                $delete_purchase_line->quantity
                                            );
                                        }
                                        //Delete deleted purchase lines
                                        PurchaseLine::where('transaction_id', $transaction->id)
                                            ->whereIn('id', $delete_purchase_line_ids)
                                            ->delete();
                                    }
                                    $transaction->purchase_lines()->saveMany($purchase_lines);
                                    //Update mapping of purchase & Sell.
                                    if (! $is_new_transaction) {
                                        $this->transactionUtil->adjustMappingPurchaseSellAfterEditingPurchase('received', $transaction, $delete_purchase_lines);
                                    }
                                    //Adjust stock over selling if found
                                    $this->productUtil->adjustStockOverSelling($transaction);

                                }
                                $output = ['success' => 1, 'msg' => __('lang_v1.opening_stock_added_successfully')];
                            }
                            //return response(['purchase_lines' => $purchase_lines], 200);
                        }
                    }
                    DB::commit();
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => $e->getMessage()];
            return back()->with('status', $output);
        }

        if (request()->ajax()) {
            return $output;
        }

        return back()->with('status', $output);
        return redirect('products')->with('status', $output);
    }
}
