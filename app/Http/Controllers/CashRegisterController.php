<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\CallNumberScheme;
use App\CashRegister;
use App\ClientCompany;
use App\Utils\CashRegisterUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $cashRegisterUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param CashRegisterUtil $cashRegisterUtil
     * @return void
     */
    public function __construct(CashRegisterUtil $cashRegisterUtil, ModuleUtil $moduleUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cash_register.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        if (!auth()->user()->can('sell.pos.add')) {
            return response(['errors' => 'Unauthorized action'], 422);
        }

        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if yes then redirect to POS screen.
        if ($this->cashRegisterUtil->countOpenedRegister() != 0) {
            return redirect()->action('SellPosController@create', ['sub_type' => $sub_type]);
        }
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('cash_register.create')->with(compact('business_locations', 'sub_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('sell.pos.add')) {
            return response(['errors' => 'Unauthorized action'], 422);
        }

        //like:repair
        $sub_type = request()->get('sub_type');

        try {
            $initial_amount = 0;
            if (!empty($request->input('amount'))) {
                $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
            }
            $user_id = $request->session()->get('user.id');
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->input('location_id');

            $call_register = CashRegister::where('business_id', $business_id)->where('location_id', $location_id)->where('status', 'open')->first();
            /*  echo "<pre>";
            print_r($call_register);exit; */
            if (!empty($call_register)) {
                $register_user_id = $call_register->user_id ?? null;
                $status = $call_register->status ?? null;
                if (($register_user_id != $user_id) && ($status = 'open')) {
                    return response(['errors' => 'Unauthorized action! You Cannot open same register. Register Already open by another user.'], 422);
                }

                /*  $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->where('user_id', $user_id)->first(); */
                $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->first();
                if (!empty($call_Number_Scheme)) {
                    //CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => 0]);
                    CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => $call_Number_Scheme->call_number_count]);
                } else {
                    $data = array(array('business_id' => $business_id, 'location_id' => $request->input('location_id'), 'user_id' => $user_id, 'call_number_count' => 0));
                    CallNumberScheme::insert($data);
                }
            } else {
                $call_Number_Scheme = CallNumberScheme::where('business_id', $business_id)->where('location_id', $location_id)->first();
                if (!empty($call_Number_Scheme)) {
                    CallNumberScheme::where('id', $call_Number_Scheme->id)->update(['call_number_count' => 0]);
                } else {
                    $data = array(array('business_id' => $business_id, 'location_id' => $request->input('location_id'), 'user_id' => $user_id, 'call_number_count' => 0));
                    CallNumberScheme::insert($data);
                }
            }

            $register = CashRegister::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'status' => 'open',
                'location_id' => $request->input('location_id'),
                'created_at' => \Carbon::now()->format('Y-m-d H:i:00'),
            ]);

            if (!empty($initial_amount)) {
                $register->cash_register_transactions()->create([
                    'amount' => $initial_amount,
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial',
                ]);
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
        }
        //return redirect("http://localhost:3000/");
        return redirect()->route('pos.new');
        //return redirect()->action('SellPosController@create', ['sub_type' => $sub_type]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $register_details_array = $this->cashRegisterUtil->getRegisterDetails($id, $business_id);
        $register_details = $register_details_array['register_details'];
        //return $register_details_array;
        $total_card = $register_details['total_card'] ?? 0.00;
        $cash_in_hand = $register_details['cash_in_hand'] ?? 0.00;
        $total_prepaid = $register_details['total_prepaid'] ?? 0.00;
        $all_card_sales = ($total_card + $total_prepaid) ?? 0.00;
        $register_details['all_card_sales'] = $all_card_sales;
        $total_sale = $register_details['total_sale'] ?? 0.00;
        $total_cash_refund = $register_details['total_cash_refund'] ?? 0.00;
        $total_card_refund = $register_details['total_card_refund'] ?? 0.00;
        $input_cash_in_hand = $register_details['input_cash_in_hand'] ?? 0.00;
        $total_refund = $register_details['total_refund'] ?? 0.00;
        $total_payment = $register_details['total_payment'] ?? 0.00;
        $total_sales = $details['transaction_details']['total_sales'] ?? 0.00;
        $total_refund_cash_new = ($total_sale - $total_sales) ?? 0.00;
        $total_payment_new = ($total_payment) ?? 0.00;
        $net_payment_new = ($total_payment_new - $total_refund) ?? 0.00;
        $total_cash = $register_details['total_cash'] ?? 0.00;
        $total_app_cash = $register_details['total_app_cash'] ?? 0.00;
        $all_cash_new = ($total_cash + $total_app_cash) ?? 0.00;
        $total_app_card = $register_details['total_app_card'] ?? 0.00;
        $all_card_new = ($total_card + $total_app_card) ?? 0.00;
        $cash_shortage = 0.00;
        $cash_overage = 0.00;
        $register_details['cash_overage_shortage'] = 0.00;
        $register_details['cash_overage_shortage_class'] = "success";
        if ($all_cash_new >= $input_cash_in_hand) {
            $cash_shortage = $all_cash_new - $input_cash_in_hand;
            $register_details['cash_overage_shortage_class'] = "danger";
            $register_details['cash_overage_shortage_label'] = __("lang_v1.cash_shortage");
            $register_details['cash_overage_shortage'] = number_format((float) $cash_shortage, 2);
        } elseif ($all_cash_new < $input_cash_in_hand) {
            $cash_overage = $input_cash_in_hand - $all_cash_new;
            $register_details['cash_overage_shortage_class'] = "success";
            $register_details['cash_overage_shortage_label'] = __("lang_v1.cash_overage");
            $register_details['cash_overage_shortage'] = number_format((float) $cash_overage, 2);
        }

        $clientApp = ClientCompany::where('business_id', $business_id)->get();
        $clientAppArray = [];
        if (!empty($clientApp)) {
            foreach ($clientApp as $key => $app) {
                $app_name_tite = $app['app_name'] ?? "";
                $app_name = str_replace(' ', '', $app['app_name']) ?? "";
                $clientAppArray[$key]['app_prepaid_title'] = "Prepaid " . $app_name_tite . " - مدفوع" . ":";
                $clientAppArray[$key]['app_cash_title'] = "Cash " . $app_name_tite . " - كاش" . ":";
                $clientAppArray[$key]['app_card_title'] = "Card " . $app_name_tite . " - شبكه" . ":";
                $clientAppArray[$key]['total_card_value'] = $register_details['total_card_' . $app_name];
                $clientAppArray[$key]['total_cash_value'] = $register_details['total_cash_' . $app_name];
                $clientAppArray[$key]['total_prepaid_value'] = $register_details['total_prepaid_' . $app_name];

            }
        }

        $register_details['net_payment_new'] = number_format((float) $net_payment_new, 2);
        $register_details['total_payment_new'] = number_format((float) $total_payment_new, 2);
        $register_details['total_refund_cash_new'] = number_format((float) $total_refund, 2);
        $register_details['clientAppArray'] = $clientAppArray;
        $register_details['all_cash_new'] = number_format((float) $all_cash_new, 2);
        $register_details['all_card_new'] = number_format((float) $all_card_new, 2);

        /*  echo "<pre/>";
        print_r($register_details);exit; */

        if (!empty($register_details)) {
            $user_id = $register_details['user_id'] ?? "";
            $open_time = $register_details['open_time'] ?? "";
            $close_time = !empty($register_details['closed_at']) ? $register_details['closed_at'] : \Carbon::now()->toDateTimeString();
            $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);
            $payment_types = $this->cashRegisterUtil->payment_types(null, false, $business_id);
            return view('cash_register.register_details')->with(compact('register_details', 'details', 'payment_types', 'close_time'));

        }

    }

    /**
     * Shows register details modal.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getRegisterDetails()
    {
        if (!auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details_array = $this->cashRegisterUtil->getRegisterDetails();
        $register_details = $register_details_array['register_details'];
        $sql = $register_details_array['sql'];
        $bindings = $register_details_array['bindings'];
        //return $register_details_array;

        $user_id = auth()->user()->id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('direct_sales_module', $business_id);

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);

        return view('cash_register.register_details')
            ->with(compact('register_details', 'details', 'payment_types', 'close_time'));
    }

    /**
     * Shows close register form.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getCloseRegister($id = null)
    {
        if (!auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $register_details_array = $this->cashRegisterUtil->getRegisterDetails($id);
        $register_details = $register_details_array['register_details'];

        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('direct_sales_module', $business_id);

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);
        return view('cash_register.close_register_modal')
            ->with(compact('register_details', 'details', 'payment_types'));
    }

    /**
     * Closes currently opened register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postCloseRegister(Request $request)
    {
        if (!auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                    'msg' => 'Feature disabled in demo!!',
                ];
                return redirect()->action('HomeController@index')->with('status', $output);
            }

            $input = $request->only(['closing_amount', 'total_card_slips', 'total_cheques',
                'closing_note']);
            $input['closing_amount'] = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $user_id = $request->input('user_id');
            $input['closed_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 'close';

            CashRegister::where('user_id', $user_id)
                ->where('status', 'open')
                ->update($input);
            $output = ['success' => 1,
                'msg' => __('cash_register.close_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0,
                'msg' => __("messages.something_went_wrong"),
            ];
        }

        return redirect()->back()->with('status', $output);
    }
}