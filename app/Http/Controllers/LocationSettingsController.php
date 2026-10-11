<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\KitchenStation;
use App\KitchenStationCategory;
use App\Printer;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationSettingsController extends Controller
{
    /**
     * All class instance.
     *
     */
    protected $printReceiptOnInvoice;
    protected $commonUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->printReceiptOnInvoice = ['1' => __('messages.yes'), '0' => __('messages.no')];
        $this->receiptPrinterType = [
            'browser' => __('lang_v1.browser_based_printing'), 'printer' => __('lang_v1.configured_printer'),
            'app' => __('lang_v1.android_printing_app'),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($location_id)
    {

        //Check for locations access permission
        if (!auth()->user()->can('business_settings.access') ||
            !auth()->user()->can_access_this_location($location_id)
        ) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $location = BusinessLocation::where('business_id', $business_id)
            ->findorfail($location_id);
        //$location = BusinessLocation::where('business_id', $business_id)->get();

        $printers = Printer::forDropdown($business_id);

        $printReceiptOnInvoice = $this->printReceiptOnInvoice;
        $receiptPrinterType = $this->receiptPrinterType;

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');
        $kitchenStations = KitchenStation::where('business_location_id', $location->id)->get();

        $business = Business::where('id', $business_id)->first();
        $enabled_modules = $this->commonUtil->allModulesEnabled($business_id);

        $kitchen_categories_dropdown = [];
        $kitchenStationsIDS = '';
        if (!empty($location_id)) {
            $kitchenStationsArray = [];
            if (!empty($business_id)) {
                $kitchenStationsArray = KitchenStation::where('business_location_id', $location_id)->where('business_id', $business_id)->get();
                //$kitchen_categories_dropdown = KitchenStationCategory::join('categories AS ca', 'kitchecnCatID', 'ca.id')->pluck('name', 'id');
                $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id, $location_id) : [];
                foreach ($categories as $key => $category) {
                    $category = (object) $category;
                    $kitchen_categories_dropdown[$category->id] = $category->name;
                }
                $kitchenStationsIDS = KitchenStationCategory::where('kitchecnID', 8)->join('categories AS ca', 'kitchecnCatID', 'ca.id')->pluck('id');

            }
            /*  echo "<pre>";
            print_r($kitchen_categories_dropdown);
            exit; */

            if (!empty($kitchenStationsArray)) {
                $kitchens = [];

                foreach ($kitchenStationsArray as $Pkey => $kitchen) {

                    /* echo "<pre>";
                    print_r($kitchen);
                    exit; */

                    $kitchenID = $kitchen['id'];
                    $station_name = $kitchen['station_name'];
                    $kitchen = $kitchen;
                    $KitchenStationCategory = KitchenStationCategory::where('kitchecnID', $kitchenID)->join('categories AS ca', 'kitchecnCatID', 'ca.id')->get();

                    /*  echo "<pre>";
                    print_r($kitchen_categories_dropdown); */

                    if (!empty($KitchenStationCategory)) {
                        $kitchenCategory = [];
                        foreach ($KitchenStationCategory as $key => $KitchenCategory) {
                            /*  $kitchenCategory[$key]['kitchenCatID'] = $KitchenCategory['kitchecnCatID'];
                            $kitchenCategory[$key]['catName'] = $KitchenCategory['name'];
                            $kitchenCategory[$key]['short_code'] = "cat-" . $KitchenCategory['short_code']; */

                            $kitchenCategory[$key] = $KitchenCategory;

                        }

                        $kitchens[$Pkey]['kitchens'] = $kitchen;
                        $kitchens[$Pkey]['kitchens']['kitchenCategory'] = $kitchenCategory;

                    }
                }
                /*  echo "<pre>";
            print_r($kitchen_categories_dropdown);
            exit; */

            }
        }
        $collectionKitchens = collect($kitchens);

        /*  echo "<pre>";
        print_r($kitchenStationsIDS);
        exit; */

        return view('location_settings.index')
            ->with(compact('location', 'printReceiptOnInvoice', 'receiptPrinterType', 'printers', 'invoice_layouts',
                'invoice_schemes', 'kitchenStations', 'business', 'collectionKitchens', 'kitchenStationsIDS', 'kitchen_categories_dropdown', 'enabled_modules'));
    }

    public function loadKitchenCategoryTable($kitchenID, $location_id)
    {
        $business_id = request()->session()->get('user.business_id');
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id, $location_id) : [];
        foreach ($categories as $key => $category) {
            $category = (object) $category;
            $kitchen_categories_dropdown[$category->id] = $category->name;
        }
        $kitchenStationsIDS = KitchenStationCategory::where('kitchecnID', $kitchenID)->join('categories AS ca', 'kitchecnCatID', 'ca.id')->pluck('id');

        /*  echo "<pre>";
        print_r($kitchen_categories_dropdown);exit;
         */
        return view('location_settings.add_kitchen_category_modal')->with(compact('kitchen_categories_dropdown', 'kitchenStationsIDS', 'location_id', 'kitchenID'));
    }

    public function addCategoryKitchens(Request $request)
    {
        if (request()->ajax()) {
            try {

                $kitchenID = request()->input('kitchenID');
                $location_id = request()->input('location_id');
                $kitchen_categories_ids = !empty(request()->input('kitchen_categories_ids')) ? request()->input('kitchen_categories_ids') : '';
                /*  echo "<pre>";
                print_r($kitchen_categories_ids);
                exit; */
                DB::beginTransaction();
                $business_id = request()->session()->get('user.business_id');
                $user_id = request()->session()->get('user.id');

                if (!empty($kitchen_categories_ids)) {
                    foreach ($kitchen_categories_ids as $KIDs) {
                        $kitchenCategoryExists = KitchenStationCategory::where('kitchecnCatID', $KIDs)->join('categories AS ca', 'kitchecnCatID', 'ca.id')->get();

                        //$kitchenCategoryExists = KitchenStationCategory::where('kitchecnID', $kitchenID)->get();
                        /*  echo "<pre>";
                        print_r($kitchenCategoryExists); */
                        //exit;

                        if (!empty($kitchenCategoryExists)) {
                            foreach ($kitchenCategoryExists as $exist) {
                                /*  echo $exist->kitchecnCatID;
                                echo "<br/>"; */

                                DB::table('kitchen_station_categories')->where('kID', $exist->kID)->where('kitchecnID', $exist->kitchecnID)->where('kitchecnCatID', $exist->kitchecnCatID)->delete();

                            }

                            DB::table('kitchen_station_categories')->insert(['kitchecnCatID' => $KIDs, 'kitchecnID' => $kitchenID]);
                            $output = ['success' => true, 'msg' => __("lang_v1.success")];

                        } else {
                            $output = ['success' => true, 'msg' => "Not inserted or updated"];

                        }
                    }

                } else {
                    $output = ['success' => true, 'msg' => "Not inserted or updated"];

                }
                DB::commit();
                //exit;

                /*  $output = ['success' => true, 'msg' => __("lang_v1.success")]; */

            } catch (\Exception $e) {
                /*  \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage()); */
                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong"),
                ];
            }

            return $output;
        }

    }

    /**
     * Update the settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSettings($location_id, Request $request)
    {
        /*  echo "<pre>";
        print_r($request->get('deleted_station_name'));exit; */
        try {
            //Check for locations access permission
            if (!auth()->user()->can('business_settings.access') ||
                !auth()->user()->can_access_this_location($location_id)
            ) {
                abort(403, 'Unauthorized action.');
            }

            $input = $request->only([
                'print_receipt_on_invoice', 'receipt_printer_type', 'number_of_thermal_print_copy', 'printer_id',
                'invoice_layout_id', 'invoice_scheme_id',
            ]);

            //Auto set to browser in demo.
            if (config('app.env') == 'demo') {
                $input['receipt_printer_type'] = 'browser';
            }

            $business_id = request()->session()->get('user.business_id');

            $location = BusinessLocation::where('business_id', $business_id)
                ->findorfail($location_id);

            $location->fill($input);
            $location->update();
            $input1 = $request->get('station_name');
            $input2 = $request->get('station_name_existing');
            $deleted_station_name = $request->get('deleted_station_name');
            $deleted_station_name = explode(',', $deleted_station_name);
            /* echo "<pre> deleted_station_name";
            print_r($deleted_station_name);
            echo "<pre> station_name_existing";
            print_r($input2);exit; */

            if (empty($input1) && empty($input2)) {
                //KitchenStation::where('business_location_id', '=', $location->id)->delete();
            }
            if (!empty($deleted_station_name)) {
                foreach ($deleted_station_name as $key => $value) {
                    $isExist = KitchenStation::where('id', '=', $value)->exists();

                    if ($isExist) {
                        /* echo "here";
                        echo "<pre>  isExist";
                        print_r($isExist);exit; */

                        KitchenStation::where('id', '=', $value)->delete();
                    }

                }
            }

            $kitchenStations = KitchenStation::where('business_location_id', $location->id)->pluck('id')->toArray();
            if (!empty($input1) && !empty($input2)) {
                $diff = array_diff($kitchenStations, array_keys($input2));
                KitchenStation::whereIn('id', $diff)->delete();
            }
            if (!empty($input2)) {
                foreach ($input2 as $key => $value) {
                    $isExist = KitchenStation::where('id', '=', $key)->exists();
                    if ($isExist) {
                        KitchenStation::where('id', '=', $key)->update(['station_name' => $value]);
                    }
                }
            }
            if (!empty($input1)) {
                foreach ($input1 as $value) {
                    KitchenStation::create([
                        'business_id' => $business_id,
                        'business_location_id' => $location->id,
                        'station_name' => $value,
                    ]);
                }
            }

            $output = [
                'success' => 1,
                'msg' => __("receipt.receipt_settings_updated"),
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong"),
            ];
        }
        //dd($output);

        return back()->with('status', $output);
    }
}
