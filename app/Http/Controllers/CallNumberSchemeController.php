<?php

namespace App\Http\Controllers;

use App\CallNumberScheme;
use App\InvoiceLayout;
use Datatables;
use Illuminate\Http\Request;

class CallNumberSchemeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $call_Number_Schemes = CallNumberScheme::where('business_id', $business_id)->select(['id', 'name', 'start_number', 'call_number_count', 'total_digits', 'last_call_number_end']);
            return Datatables::of($call_Number_Schemes)
                ->addColumn(
                    'action',
                    '<button type="button" data-href="{{action(\'CallNumberSchemeController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".call_number_edit_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    '
                )
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->removeColumn('id')
                ->removeColumn('is_default')
                ->removeColumn('scheme_type')
                ->rawColumns([5, 0])
                ->make(false);
        }

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)->with(['locations'])->get();
        return view('invoice_scheme.index')->with(compact('invoice_layouts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        return view('call_number_scheme.create');
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
       
        try {
            $input = $request->only(['name', 'total_digits', 'last_call_number_end']);
            $business_id = $request->session()->get('user.business_id');
            $user_id     = request()->session()->get('user.id');
            $input['business_id'] = $business_id;
            $input['location_id'] = 1;
            $input['user_id'] = $user_id;
            $input['call_number_count'] = 1;
            CallNumberScheme::create($input);
            $output = ['success' => true, 'msg' => __("invoice.added_success")];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => false, 'msg' => __("messages.something_went_wrong")];
        }

        try {
            $input = $request->only(['name', 'scheme_type', 'prefix', 'start_number', 'total_digits']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            if (!empty($request->input('is_default'))) {
                //get_default
                $default = InvoiceScheme::where('business_id', $business_id)->where('is_default', 1)->update(['is_default' => 0]);
                $input['is_default'] = 1;
            }
            InvoiceScheme::create($input);
            $output = ['success' => true, 'msg' => __("invoice.added_success")];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => false, 'msg' => __("messages.something_went_wrong")];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $call_Number = CallNumberScheme::where('business_id', $business_id)->find($id);
        return view('call_number_scheme.edit')->with(compact('call_Number'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input = $request->only(['name', 'total_digits', 'last_call_number_end']);
            $invoice = CallNumberScheme::where('id', $id)->update($input);
            $output = ['success' => true, 'msg' => __('invoice.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => false, 'msg' => __("messages.something_went_wrong")];
        }

        return $output;
    }
}
