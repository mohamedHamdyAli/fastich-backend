<?php

namespace App\Http\Controllers;

use App\KitchenStation;
use App\SellingPriceGroup;
use App\Utils\ModuleUtil;
use App\Utils\ResponseUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Response;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Permission;

class KitchenStationController extends Controller
{
    /**
     * All Utils instance.
     *
     */
//    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
//    public function __construct(ModuleUtil $moduleUtil)
//    {
//        $this->moduleUtil = $moduleUtil;
//    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $input = $request->all();
        $kitchenStations = '';
        if (!empty($input['location_id'])){
            $kitchenStations = KitchenStation::whereIn('business_location_id',$input['location_id'])->pluck('station_name','id');
        }
        
        return Response::json(['success' => true,
            'data'    => $kitchenStations,
            'message' => 'success']);
    }
}
