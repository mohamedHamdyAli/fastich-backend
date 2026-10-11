<?php

namespace App\Http\Controllers;

use App\ClientCompany;
use App\Utils\TaxUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ClientAppController extends Controller
{

    /**
     * All Utils instance.
     *
     */
    protected $taxUtil;

    /**
     * Constructor
     *
     * @param TaxUtil $taxUtil
     * @return void
     */
    public function __construct(TaxUtil $taxUtil)
    {
        $this->taxUtil = $taxUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $business_id = request()->session()->get('user.business_id');

        //return response(['clientApp' => $clientApp], '200');

        //return view('business.clientapp', compact('clientApp'));

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $clientApp = ClientCompany::where('business_id', $business_id)->get();

            return Datatables::of($clientApp)
                ->addColumn(
                    'action',
                    '@can("tax_rate.update")
                    <button data-href="{{action(\'ClientAppController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_client_app_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("tax_rate.delete")
                        <button data-href="{{action(\'ClientAppController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_client_app_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->removeColumn('for_tax_group')
                ->removeColumn('id')
                ->removeColumn('created_at')
                ->removeColumn('updated_at')
                ->removeColumn('business_id')
                ->rawColumns([0, 1])
                ->make(false);
        }
        return view('client_app.index');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('client_app.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('client_app.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('client_app.create')) {
            abort(403, 'Unauthorized action.');
        }

        // try {
        $input = $request->only(['app_name']);
        $input['business_id'] = $request->session()->get('user.business_id');
        //return $input;

        $client_app = ClientCompany::create($input);
        $output = ['success' => true,
            'data' => $client_app,
            'msg' => __("client_app.added_success"),
        ];
        /* } catch (\Exception $e) {
        \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

        $output = ['success' => false,
        'msg' => __("messages.something_went_wrong"),
        ];
        } */

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('tax_rate.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $client_app = ClientCompany::where('business_id', $business_id)->find($id);
            return view('client_app.edit')->with(compact('client_app'));
        }
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
        if (!auth()->user()->can('client_app.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['app_name']);
                $business_id = $request->session()->get('user.business_id');
                $client_app = ClientCompany::where('business_id', $business_id)->findOrFail($id);
                $client_app->app_name = $input['app_name'];
                $client_app->save();
                $output = ['success' => true,
                    'msg' => __("client_app.updated_success"),
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong"),
                ];
            }
            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('client_app.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //update group tax amount
                $business_id = request()->user()->business_id;
                $client_app = ClientCompany::where('business_id', $business_id)->findOrFail($id);
                $client_app->delete();
                $output = ['success' => true,
                    'msg' => __("client_app.deleted_success"),
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong"),
                ];
            }
            return $output;
        }
    }
}
