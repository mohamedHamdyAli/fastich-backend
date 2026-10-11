<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Charts\CommonChart;
use App\Currency;
use App\TermsConditions;
use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use Carbon;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Neodynamic\SDK\Web\WebClientPrint;

/*//Includes WebClientPrint classes
include_once app_path() . '\WebClientPrint\WebClientPrint.php';
use Session;
 */

class HomeController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $commonUtil;
    protected $restUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil,
        Util $commonUtil,
        RestaurantUtil $restUtil
    )
    {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
        $this->restUtil = $restUtil;
    }

    public function getColor($num)
    {
        $hash = md5('color' . $num); // modify 'color' to get a different palette
        /* return array(
        hexdec(substr($hash, 0, 2)), // r
        hexdec(substr($hash, 2, 2)), // g
        hexdec(substr($hash, 4, 2))); //b */

        return 'rgb(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ')'; #using the inbuilt random function

    }

    public function saveAgreement()
    {
        $business_id = request()->session()->get('user.business_id');
        $today = \Carbon::now()->format("Y-m-d H:i:s");
        $user_id = !empty(request()->user_id) ? request()->user_id : auth()->user()->id;
        $ip = \Request::ip();
        $clientIP = request()->ip();
        $digits = 6;

        $otp = rand(pow(10, $digits - 1), pow(10, $digits) - 1);

        $saveData = [];
        $saveData['business_id'] = [$business_id];
        $saveData['user_id'] = [$user_id];
        $saveData['today'] = [$today];
        $saveData['ip'] = [$ip];
        $saveData['otp'] = [$otp];
        $saveData['clientIP'] = [$clientIP];
        $data = array(
            array('business_id' => $business_id, 'created_date' => $today, 'user_id' => $user_id, 'ip' => $ip, 'otp' => $otp, 'clientIP' => $clientIP, 'status' => 1),
        );
        $res = TermsConditions::insert($data);
        if ($res) {
            $result['success'] = [1];
            $result['msg'] = ['Terms & Condition Done'];
            return $result;
        } else {
            $result['success'] = [0];
            $result['msg'] = ['Terms & Condition not done'];
            return $result;
        }
        /* dump($res);

    dump($saveData); */

    }


    public function getTermsAndConditionStatus($business_id)
    {
        $termsConditionNotDone = TermsConditions::where('business_id', $business_id)
            ->where('status', 1)
            ->first();

        return !empty($termsConditionNotDone) ? $termsConditionNotDoneStatus = 1 : $termsConditionNotDoneStatus = 0;
    }

    public function get_date_filters($financial_year)
    {
        $yesterday = \Carbon::yesterday();
        $date_filters['yesterday'] = date('Y-m-d', strtotime($yesterday));
        $date_filters['this_fy'] = $financial_year;
        $date_filters['this_month']['start'] = date('Y-m-01');
        $date_filters['this_month']['end'] = date('Y-m-t');
        $date_filters['this_week']['start'] = date('Y-m-d', strtotime('monday this week'));
        $date_filters['this_week']['end'] = date('Y-m-d', strtotime('sunday this week'));
        return $date_filters;
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $business_id = request()->session()->get('user.business_id');
        $termsConditionNotDone = TermsConditions::where('business_id', $business_id)
            ->where('status', 1)
            ->first();

        if (!empty($termsConditionNotDone)) {
            $termsConditionNotDoneStatus = 1;
        } else {
            $termsConditionNotDoneStatus = 0;
        }

        if (!auth()->user()->can('dashboard.data')) {
            return view('home.index', compact('termsConditionNotDoneStatus'));
        }

        $financialYear = $this->businessUtil->getCurrentFinancialYear($business_id);
        $currency = Currency::where('id', request()->session()->get('business.currency_id'))->first();
        $all_locations = BusinessLocation::forDropdownReport($business_id)->toArray();
        $date_filters = $this->get_date_filters($financialYear);
        return view('home.index', compact('date_filters', 'currency', 'financialYear', 'all_locations', 'termsConditionNotDoneStatus'));
    }

    public function get_home_widgets(Request $request)
    {
        //Get Dashboard widgets from module
        $module_widgets = $this->moduleUtil->getModuleData('dashboard_widget');

        $widgets = [];

        foreach ($module_widgets as $widget_array) {
            if (!empty($widget_array['position'])) {
                $widgets[$widget_array['position']][] = $widget_array['widget'];
            }
        }
        return response()->json(['widgets' => $widgets]);
    }

    // Chart for current financial year
    // public function chart_for_this_fy(Request $request)
    // {
    //     //Chart for sells this financial year

    //     $request->validate([
    //         "financialYear" => "required",
    //         "all_locations" => "required",
    //         "currency" => "required"
    //     ]);

    //     $financialYear = $request->financialYear;
    //     $all_locations = $request->all_locations;
    //     $currency = $request->currency;

    //     $business_id = request()->session()->get('user.business_id');


    //     $sells_this_fy = $this->transactionUtil->getSellsCurrentFy($business_id, $financialYear['start'], $financialYear['end']);

    //     $labels = [];
    //     $values = [];

    //     $date = strtotime($financialYear['start']);
    //     $last = date('m-Y', strtotime($financialYear['end']));

    //     $fy_months = [];
    //     do {
    //         $month_year = date('m-Y', $date);
    //         $NextMonth = date('M-Y', $date);

    //         $fy_months[] = $month_year;

    //         $labels[] = $NextMonth;

    //         //$labels[] = Carbon\Carbon::createFromFormat('m-Y', $month_year)->format('M-Y');
    //         $date = strtotime('+1 month', $date);

    //         if (!empty($sells_this_fy[$month_year])) {
    //             $values[] = (float)$sells_this_fy[$month_year];
    //         } else {
    //             $values[] = 0;
    //         }
    //     } while ($month_year != $last);

    //     /* echo "<pre>";
    //     print_r($labels);exit; */

    //     $fy_sells_by_location = $this->transactionUtil->getSellsCurrentFy($business_id, $financialYear['start'], $financialYear['end'], true);
    //     $fy_sells_by_location_data = [];

    //     foreach ($all_locations as $loc_id => $loc_name) {
    //         $values_data = [];
    //         foreach ($fy_months as $month) {
    //             $sell = $fy_sells_by_location->first(function ($item) use ($loc_id, $month) {
    //                 return $item->yearmonth == $month &&
    //                     $item->location_id == $loc_id;
    //             });

    //             if (!empty($sell)) {
    //                 $values_data[] = (float)$sell->total_sells;
    //             } else {
    //                 $values_data[] = 0;
    //             }
    //         }
    //         $fy_sells_by_location_data[$loc_id]['loc_label'] = $loc_name;
    //         $fy_sells_by_location_data[$loc_id]['loc_rgb'] = $this->getColor($loc_id);
    //         $fy_sells_by_location_data[$loc_id]['values'] = $values_data;
    //     }

    //     $sells_chart_2 = new CommonChart;
    //     $title = __( 'home.total_sells', ['currency' => $currency["code"]]);
    //     $sells_chart_2->labels($labels)
    //         ->options($this->__chartOptions($title));
    //     if (!empty($fy_sells_by_location_data)) {
    //         foreach ($fy_sells_by_location_data as $location_sell) {
    //             $sells_chart_2->dataset($location_sell['loc_label'], 'column', $location_sell['values']);
    //         }
    //     }
    //     if (count($all_locations) > 1) {
    //         $sells_chart_2->dataset(__('report.all_locations'), 'column', $values);
    //     }
    //     return response()->json(
    //         [
    //             'sells_chart_2' => $sells_chart_2,
    //              "title" => $title,
    //              "sells_this_fy" => $sells_this_fy,
    //              "labels" => $labels,
    //              "fy_months" => $fy_months,
    //              "values" => $values,
    //              "fy_sells_by_location" => $fy_sells_by_location,
    //             "fy_sells_by_location_data" => $fy_sells_by_location_data,
    //         ]);
    // }
    
    public function chart_for_this_fy(Request $request)
    {
        //Chart for sells this financial year

        $request->validate([
            "financialYear" => "required",
            "all_locations" => "required",
            "currency" => "required"
        ]);

        $financialYear = $request->financialYear;
        $all_locations = $request->all_locations;
        $currency = $request->currency;

        $business_id = request()->session()->get('user.business_id');
        $labels = [];

        $date = strtotime($financialYear['start']);
        $last = date('m-Y', strtotime($financialYear['end']));
        $fy_months = [];
        $sells_this_fy_by_month = [];
        do {
            $month_year = date('m-Y', $date);
            $labels[] = date('M-Y', $date);
            $fy_months[] = $month_year;
            $sells_this_fy_by_month[$month_year] = 0;
            $date = strtotime('+1 month', $date);
        } while ($month_year != $last);


        $fy_sells_by_location = $this->transactionUtil->getSellsCurrentFy($business_id, $financialYear['start'], $financialYear['end'], true);
        $dataset = [];
        foreach ($all_locations as $loc_id => $loc_name) {
            $values_data = [];
            foreach ($fy_months as $month) {
                $sell = $fy_sells_by_location->first(function ($item) use ($loc_id, $month) {
                    return $item->yearmonth == $month &&
                    $item->location_id == $loc_id;
                });

                if (!empty($sell)) {
                    $values_data[] = (float) $sell->total_sells;
                    $sells_this_fy_by_month[$month] += (float)$sell->total_sells;
                } else {
                    $values_data[] = 0;
                }
            }
            $dataset[] = [
                "type" => "column",
                "name" => $loc_name,
                "data" => $values_data
            ];
        }

        $title = __( 'home.total_sells', ['currency' => $currency["code"]]);
        if (count($all_locations) > 1) {
            $dataset[] = [
                "type" => "column",
                "name" => __('report.all_locations'),
                "data" => array_values($sells_this_fy_by_month)
            ];
        }
        return response()->json(
            [
                 "title" => $title,
                 "labels" => $labels,
                 "dataset" => $dataset,
            ]);
    }


public function sell_chart_one(Request $request)
    {
        $request->validate([
            "currency" => "required",
            "all_locations" => "required"
        ]);
        // Request Variables
        $all_locations = $request->all_locations;
        $currency = $request->currency;

        $business_id = request()->session()->get('user.business_id');
        // Create labels
        $labels = [];
        $dates = [];
        $total_loc_sell_by_date = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;
            $labels[] = date('j M Y', strtotime($date));
            $total_loc_sell_by_date[$date] = 0;
        }
        $title = __('home.total_sells', ['currency' => $currency["code"]]);
        // Location Sells Calculation
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $permitted_locations = implode(',', $permitted_locations);
        } 
        $sells_by_location = DB::select('CALL GetSellsLastNDays(?, ?, ?)', [$business_id, 7, $permitted_locations]);
        // $sells_by_location = $this->transactionUtil->getSellsLastNDays($business_id,7, true);
        logger($sells_by_location);
        $dataset = [];
        foreach ($all_locations as $loc_id => $loc_name) {
            $values = [];
            foreach ($dates as $date) {
                // $sell = $sells_by_location->first(function ($item) use ($loc_id, $date) {
                //     return $item->date == $date &&
                //         $item->location_id == $loc_id;
                // });
                
                $result = array_filter($sells_by_location, function($item) use($loc_id, $date) {
                    
                    return $item->DATE == $date &&
                        $item->location_id == $loc_id;
                });
                
                $sell = reset($result);

                if (!empty($sell)) {
                    $values[] = (float)$sell->total_sells;
                    $total_loc_sell_by_date[$date] += (float)$sell->total_sells;
                } else {
                    $values[] = 0;
                }
            }
            $dataset[] = [
                "type" => "column",
                "name" => $loc_name,
                "data" => $values
            ];
        }

        if (count($all_locations) > 1) {
            $dataset[] = [
                "type" => "column",
                "name" => __('report.all_locations'),
                "data" => array_values($total_loc_sell_by_date)
            ];
        }

        return response()->json(
            [
                "title" => $title,
                "labels" => $labels,
                "dataset" => $dataset,
            ]);
    }
    // Chart for last 7 days sales
    // public function sell_chart_one(Request $request)
    // {
    //     $request->validate([
    //         "currency" => "required",
    //         "all_locations" => "required"
    //     ]);
    //     // Request Variables
    //     $all_locations = $request->all_locations;
    //     $currency = $request->currency;

    //     $business_id = request()->session()->get('user.business_id');
    //     $sells_last_30_days = $this->transactionUtil->getSellsLastNDays($business_id, 7);
    //     $labels = [];
    //     $all_sell_values = [];
    //     $dates = [];

    //     for ($i = 6; $i >= 0; $i--) {
    //         $date = \Carbon::now()->subDays($i)->format('Y-m-d');
    //         $dates[] = $date;

    //         $labels[] = date('j M Y', strtotime($date));

    //         if (!empty($sells_last_30_days[$date])) {
    //             $all_sell_values[] = (float)$sells_last_30_days[$date];
    //         } else {
    //             $all_sell_values[] = 0;
    //         }
    //     }
    //     $sells_chart_1 = new CommonChart;
    //     $title = __('home.total_sells', ['currency' => $currency["code"]]);
    //     $sells_chart_1->labels($labels)->options($this->__chartOptions($title));
    //     // Location Sells Calculation
    //     $location_sells = [];
    //     $sells_by_location = $this->transactionUtil->getSellsLastNDays($business_id,7, true);
    //     $j = 0;
    //     $RGBCount = count($all_locations);

    //     foreach ($all_locations as $loc_id => $loc_name) {

    //         $values = [];
    //         foreach ($dates as $date) {
    //             $sell = $sells_by_location->first(function ($item) use ($loc_id, $date) {
    //                 return $item->date == $date &&
    //                     $item->location_id == $loc_id;
    //             });

    //             if (!empty($sell)) {
    //                 $values[] = (float)$sell->total_sells;
    //             } else {
    //                 $values[] = 0;
    //             }
    //         }

    //         $location_sells[$loc_id]['loc_label'] = $loc_name;
    //         $location_sells[$loc_id]['loc_rgb'] = $this->getColor($loc_id);
    //         $location_sells[$loc_id]['values'] = $values;
    //         $j = $j + 1;
    //     }

    //     if (!empty($location_sells)) {
    //         foreach ($location_sells as $location_sell) {
    //             $sells_chart_1->dataset($location_sell['loc_label'], 'column', $location_sell['values']);
    //         }
    //     }

    //     if (count($all_locations) > 1) {
    //         $sells_chart_1->dataset(__('report.all_locations'), 'column', $all_sell_values);
    //     }

    //     return response()->json(['sells_chart_1' => $sells_chart_1, "title" => $title, "sells_last_30_days" => $sells_last_30_days,"all_sell_values" => $all_sell_values, "sells_by_location" => $sells_by_location, "location_sells" => $location_sells]);
    //     // return response()->json(['sells_chart_1' => $sells_chart_1, "title" => $title]);
    // }

    public function printIndex()
    {

        $wcppScript = WebClientPrint::createWcppDetectionScript(action('WebClientPrintController@processRequest'), Session::getId());

        return view('home.printDemo', ['wcppScript' => $wcppScript]);
    }

    public function printESCPOS()
    {
        $wcpScript = WebClientPrint::createScript(action('WebClientPrintController@processRequest'), action('PrintESCPOSController@printCommands'), Session::getId());

        return view('home.printESCPOS', ['wcpScript' => $wcpScript]);
    }

    // Missing total_purchase
    public function getPurchaseTotals(){
        if (request()->ajax()) {
            $start = request()->start;
            $end = request()->end;
            $location_id = request()->location_id;
            $business_id = request()->session()->get('user.business_id');

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start, $end, $location_id);

            $output = $purchase_details;
            $output['total_purchase_inc_tax'] = !empty($purchase_details['total_purchase_inc_tax']) ? $purchase_details['total_purchase_inc_tax'] : 0;
            return $output;
        }
    }

    // public function getTransactionalTotals(Request $request)
    // {
    //     if (request()->ajax()) {
    //         $start = request()->start;
    //         $end = request()->end;

    //         $location_id = request()->location_id;
    //         $business_id = request()->session()->get('user.business_id');

    //         $transaction_types = [
    //             'purchase_return', 'sell_return', 'expense',
    //         ];

    //         $transaction_totals = $this->transactionUtil->getTransactionTotals(
    //             $business_id,
    //             $transaction_types,
    //             $start,
    //             $end,
    //             $location_id
    //         );
    //         $output = $transaction_totals;
    //         $output['total_sell_return_inc_tax'] = !empty($transaction_totals['total_sell_return_inc_tax']) ? $transaction_totals['total_sell_return_inc_tax'] : 0;
    //         return $output;
    //     }
    // }
    
    public function getTransactionalTotals(Request $request)
    {
        if (request()->ajax()) {
            $start = request()->start;
            $end = request()->end;

            $location_id = request()->location_id;
            $business_id = request()->session()->get('user.business_id');

            // $transaction_types = [
            //     'purchase_return', 'sell_return', 'expense',
            // ];

            // $transaction_totals = $this->transactionUtil->getTransactionTotals(
            //     $business_id,
            //     $transaction_types,
            //     $start,
            //     $end,
            //     $location_id
            // );
            $transaction_totals = DB::select('CALL GetTransactionTotals(?, ?, ?, ?, ?)',
                [
                    $business_id,
                    $start,
                    $end,
                    $location_id ? $location_id : null,
                    null
                ]
            );
            logger($transaction_totals);
            return $transaction_totals[0];
        }
    }

    // public function getSellTotals()
    // {
    //     if (request()->ajax()) {
    //         $start = request()->start;
    //         $end = request()->end;
    //         $location_id = request()->location_id;
    //         $business_id = request()->session()->get('user.business_id');
    //          $sell_details = $this->transactionUtil->getSellTotals($business_id, $start, $end, $location_id);
    //         //$sell_details = DB::select('CALL GetSellTotals(?, ?, ?, ?, ?)', [$business_id, $start, $end, $location_id, null]);
    //         logger($sell_details);
    //         $output = [];
    //         $output['total_sell_inc_tax'] = !empty($sell_details['total_sell_inc_tax']) ? $sell_details['total_sell_inc_tax'] : 0;
    //         $output['invoice_due'] = $sell_details['invoice_due'];
    //         $output['sql'] = $sell_details['sql'];
    //         $output['bindings'] = $sell_details['bindings'];
    //         $output['final_total'] = $sell_details['final_total'];
    //         $output['total_paid'] = $sell_details['total_paid'];
    //         return $output;
    //     }
    // }
    
    public function getSellTotals()
    {
        if (request()->ajax()) {
            $start = request()->start;
            $end = request()->end;
            $location_id = request()->location_id;
            $business_id = request()->session()->get('user.business_id');
            // $sell_details = $this->transactionUtil->getSellTotals($business_id, $start, $end, $location_id);
            $sell_details = DB::select('CALL GetSellTotals(?, ?, ?, ?, ?)', [$business_id, $start, $end, $location_id, null]);
            logger('sell_details', $sell_details);
            $output = [];
            if (!empty($sell_details)) {
                $output = $sell_details[0];
            } else {
                $output['total_sell_inc_tax'] = 0;
                $output['invoice_due'] = 0;
            }
            return $output;
        }
    }



    /**
     * Retrieves purchase and sell details for a given time period.
     *
     * @return \Illuminate\Http\Response
     */
    public function getTotals()
    {
        if (request()->ajax()) {
            $start = request()->start;
            $end = request()->end;
            $location_id = request()->location_id;
            $business_id = request()->session()->get('user.business_id');

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start, $end, $location_id);

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start, $end, $location_id);

            $transaction_types = [
                'purchase_return', 'sell_return', 'expense',
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start,
                $end,
                $location_id
            );

            $total_purchase_inc_tax = !empty($purchase_details['total_purchase_inc_tax']) ? $purchase_details['total_purchase_inc_tax'] : 0;
            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];

            $total_purchase = $total_purchase_inc_tax - $total_purchase_return_inc_tax;
            $output = $purchase_details;
            $output['total_purchase'] = $total_purchase;

            $total_sell_inc_tax = !empty($sell_details['total_sell_inc_tax']) ? $sell_details['total_sell_inc_tax'] : 0;
            $total_sell_return_inc_tax = !empty($transaction_totals['total_sell_return_inc_tax']) ? $transaction_totals['total_sell_return_inc_tax'] : 0;

            $output['total_sell'] = $total_sell_inc_tax - $total_sell_return_inc_tax;

            $output['invoice_due'] = $sell_details['invoice_due'];
            $output['total_expense'] = $transaction_totals['total_expense'];
            $output['sql'] = $sell_details['sql'];
            $output['bindings'] = $sell_details['bindings'];
            $output['final_total'] = $sell_details['final_total'];
            $output['total_paid'] = $sell_details['total_paid'];

            return $output;
        }
    }

    /**
     * Retrieves sell products whose available quntity is less than alert quntity.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProductStockAlert()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $query = VariationLocationDetails::join(
                'product_variations as pv',
                'variation_location_details.product_variation_id',
                '=',
                'pv.id'
            )
                ->join(
                    'variations as v',
                    'variation_location_details.variation_id',
                    '=',
                    'v.id'
                )
                ->join(
                    'products as p',
                    'variation_location_details.product_id',
                    '=',
                    'p.id'
                )
                ->leftjoin(
                    'business_locations as l',
                    'variation_location_details.location_id',
                    '=',
                    'l.id'
                )
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('p.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->where('p.is_inactive', 0)
                ->whereRaw('variation_location_details.qty_available <= p.alert_quantity');

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('variation_location_details.location_id', $permitted_locations);
            }

            $products = $query->select(
                'p.name as product',
                'p.type',
                'pv.name as product_variation',
                'v.name as variation',
                'l.name as location',
                'variation_location_details.qty_available as stock',
                'u.short_name as unit'
            )
                ->groupBy('variation_location_details.id')
                ->orderBy('stock', 'asc');

            return Datatables::of($products)
                ->editColumn('product', function ($row) {
                    if ($row->type == 'single') {
                        return $row->product;
                    } else {
                        return $row->product . ' - ' . $row->product_variation . ' - ' . $row->variation;
                    }
                })
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $stock . '</span> ' . $row->unit;
                })
                ->removeColumn('unit')
                ->removeColumn('type')
                ->removeColumn('product_variation')
                ->removeColumn('variation')
                ->rawColumns([2])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchasePaymentDues()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today = \Carbon::now()->format("Y-m-d H:i:s");

            $query = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                ->leftJoin(
                    'transaction_payments as tp',
                    'transactions.id',
                    '=',
                    'tp.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.payment_status', '!=', 'paid')
                ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(c.pay_term_type = 'days', c.pay_term_number, 30 * c.pay_term_number) DAY), '$today') <= 7");

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            $dues = $query->select(
                'transactions.id as id',
                'c.name as supplier',
                'ref_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                ->groupBy('transactions.id');

            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = !empty($row->total_paid) ? $row->total_paid : 0;
                    $due = $row->final_total - $total_paid;
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        $due . '</span>';
                })
                ->editColumn('ref_no', function ($row) {
                    if (auth()->user()->can('purchase.view')) {
                        return '<a href="#" data-href="' . action('PurchaseController@show', [$row->id]) . '"
                                    class="btn-modal" data-container=".view_modal">' . $row->ref_no . '</a>';
                    }
                    return $row->ref_no;
                })
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([1, 2])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
     
    public function getSalesPaymentDues()
    {
        if (request()->ajax()) {
            
            $business_id = request()->session()->get('user.business_id');
            $dues = DB::select('CALL GetSalesPaymentDues(?)', [$business_id]);
            // $today = \Carbon::now()->format("Y-m-d H:i:s");

            // $query = Transaction::join(
            //     'contacts as c',
            //     'transactions.contact_id',
            //     '=',
            //     'c.id'
            // )
            //     ->leftJoin(
            //         'transaction_payments as tp',
            //         'transactions.id',
            //         '=',
            //         'tp.transaction_id'
            //     )
            //     ->where('transactions.business_id', $business_id)
            //     ->where('transactions.type', 'sell')
            //     ->where('transactions.payment_status', '!=', 'paid')
            //     ->whereNotNull('transactions.pay_term_number')
            //     ->whereNotNull('transactions.pay_term_type')
            //     ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");

            // //Check for permitted locations of a user
            // $permitted_locations = auth()->user()->permitted_locations();
            // if ($permitted_locations != 'all') {
            //     $query->whereIn('transactions.location_id', $permitted_locations);
            // }

            // $dues = $query->select(
            //     'transactions.id as id',
            //     'c.name as customer',
            //     'transactions.invoice_no',
            //     'final_total',
            //     DB::raw('SUM(tp.amount) as total_paid')
            // )
            //     ->groupBy('transactions.id');

            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = !empty($row->total_paid) ? $row->total_paid : 0;
                    $due = $row->final_total - $total_paid;
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        $due . '</span>';
                })
                ->editColumn('invoice_no', function ($row) {
                    if (auth()->user()->can('sell.view')) {
                        return '<a href="#" data-href="' . action('SellController@show', [$row->id]) . '"
                                    class="btn-modal" data-container=".view_modal">' . $row->invoice_no . '</a>';
                    }
                    return $row->invoice_no;
                })
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([1, 2])
                ->make(false);
        }
    }
     
    // public function getSalesPaymentDues()
    // {
    //     if (request()->ajax()) {
    //         $business_id = request()->session()->get('user.business_id');
    //         $today = \Carbon::now()->format("Y-m-d H:i:s");

    //         $query = Transaction::join(
    //             'contacts as c',
    //             'transactions.contact_id',
    //             '=',
    //             'c.id'
    //         )
    //             ->leftJoin(
    //                 'transaction_payments as tp',
    //                 'transactions.id',
    //                 '=',
    //                 'tp.transaction_id'
    //             )
    //             ->where('transactions.business_id', $business_id)
    //             ->where('transactions.type', 'sell')
    //             ->where('transactions.payment_status', '!=', 'paid')
    //             ->whereNotNull('transactions.pay_term_number')
    //             ->whereNotNull('transactions.pay_term_type')
    //             ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");

    //         //Check for permitted locations of a user
    //         $permitted_locations = auth()->user()->permitted_locations();
    //         if ($permitted_locations != 'all') {
    //             $query->whereIn('transactions.location_id', $permitted_locations);
    //         }

    //         $dues = $query->select(
    //             'transactions.id as id',
    //             'c.name as customer',
    //             'transactions.invoice_no',
    //             'final_total',
    //             DB::raw('SUM(tp.amount) as total_paid')
    //         )
    //             ->groupBy('transactions.id');

    //         return Datatables::of($dues)
    //             ->addColumn('due', function ($row) {
    //                 $total_paid = !empty($row->total_paid) ? $row->total_paid : 0;
    //                 $due = $row->final_total - $total_paid;
    //                 return '<span class="display_currency" data-currency_symbol="true">' .
    //                     $due . '</span>';
    //             })
    //             ->editColumn('invoice_no', function ($row) {
    //                 if (auth()->user()->can('sell.view')) {
    //                     return '<a href="#" data-href="' . action('SellController@show', [$row->id]) . '"
    //                                 class="btn-modal" data-container=".view_modal">' . $row->invoice_no . '</a>';
    //                 }
    //                 return $row->invoice_no;
    //             })
    //             ->removeColumn('id')
    //             ->removeColumn('final_total')
    //             ->removeColumn('total_paid')
    //             ->rawColumns([1, 2])
    //             ->make(false);
    //     }
    // }

    public function loadMoreNotifications()
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'DESC')->paginate(10);

        if (request()->input('page') == 1) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        $notifications_data = $this->commonUtil->parseNotifications($notifications);

        return view('layouts.partials.notification_list', compact('notifications_data'));
    }

    /**
     * Function to count total number of unread notifications
     *
     * @return json
     */
    public function getTotalUnreadNotifications()
    {
        $unread_notifications = auth()->user()->unreadNotifications;
        $total_unread = $unread_notifications->count();

        $notification_html = '';
        $modal_notifications = [];
        foreach ($unread_notifications as $unread_notification) {
            if (isset($data['show_popup'])) {
                $modal_notifications[] = $unread_notification;
                $unread_notification->markAsRead();
            }
        }
        if (!empty($modal_notifications)) {
            $notification_html = view('home.notification_modal')->with(['notifications' => $modal_notifications])->render();
        }

        return [
            'total_unread' => $total_unread,
            'notification_html' => $notification_html,
        ];
    }

    private function __chartOptions($title)
    {
        return [
            'yAxis' => [
                'title' => [
                    'text' => $title,
                ],
            ],
            'xAxis' => [
                'title' => [
                    'text' => "",
                ],
            ],
            'legend' => [
                'align' => 'right',
                /* 'verticalAlign' => 'left',
            'floating' => true,
            'layout' => 'vertical', */
            ],
            'plotOptions' => [
                'line' => [
                    'dataLabels' => [
                        'enabled' => true,
                    ],
                    'enableMouseTracking' => false,
                ],
            ],

        ];
    }

    public function getCalendar()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->restUtil->is_admin(auth()->user(), $business_id);
        $is_superadmin = auth()->user()->can('superadmin');
        if (request()->ajax()) {
            $data = [
                'start_date' => request()->start,
                'end_date' => request()->end,
                'user_id' => ($is_admin || $is_superadmin) && !empty(request()->user_id) ? request()->user_id : auth()->user()->id,
                'location_id' => !empty(request()->location_id) ? request()->location_id : null,
                'business_id' => $business_id,
                'events' => request()->events ?? [],
                'color' => '#007FFF',
            ];
            $events = [];

            if (in_array('bookings', $data['events'])) {
                $events = $this->restUtil->getBookingsForCalendar($data);
            }

            $module_events = $this->moduleUtil->getModuleData('calendarEvents', $data);

            foreach ($module_events as $module_event) {
                $events = array_merge($events, $module_event);
            }

            return $events;
        }

        $all_locations = BusinessLocation::forDropdown($business_id)->toArray();
        $users = [];
        if ($is_admin) {
            $users = User::forDropdown($business_id, false);
        }

        $event_types = [
            'bookings' => [
                'label' => __('restaurant.bookings'),
                'color' => '#007FFF',
            ],
        ];
        $module_event_types = $this->moduleUtil->getModuleData('eventTypes');
        foreach ($module_event_types as $module_event_type) {
            $event_types = array_merge($event_types, $module_event_type);
        }

        return view('home.calendar')->with(compact('all_locations', 'users', 'event_types'));
    }

    public function showNotification($id)
    {
        $notification = DatabaseNotification::find($id);

        $data = $notification->data;

        $notification->markAsRead();

        return view('home.notification_modal')->with([
            'notifications' => [$notification],
        ]);
    }
}
