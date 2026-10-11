<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use App\Product;
use App\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\BusinessLocation;



class DeliverectIntegrationController extends Controller
{
    /**
     * Display the Deliverect integration index view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $res = $this->getCurrentBussinessStatus($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);
        
        // $business_locations->prepend(__('lang_v1.none'), 'none');
        return view('deliverect.index', [
            'business_id' => $business_id, 
            "deliverect" => $res, 
            "business_locations" => $business_locations
        ]);
    }

    /**
     * Handle the Deliverect onboarding process.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onBoard(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $endpoint = "/api/deliverect/{$business_id}/on-board-with-deliverect/";

        $internalRequest = Request::create($endpoint, 'POST', [
            'client_id' => $request->input('client_id'),
            'client_secret' => $request->input('client_secret'),
        ]);

        $response = Route::dispatch($internalRequest);
        $content = $response->getOriginalContent();
        // log the response
        \Log::info('Deliverect onboarding response: ' . json_encode($content));
        if (isset($content['success']) && $content['success'] === true) {
            $res = $this->getCurrentBussinessStatus($business_id);

            // Flash the success message to the session
            session()->flash('success', 'Deliverect authentication successful.');
            $business_locations = BusinessLocation::forDropdown($business_id);
            return view('deliverect.index', [
                'business_id' => $business_id,
                'deliverect' => $res,
                'business_locations' => $business_locations,
            ]);
        } else {
            // Flash the error message to the session
            session()->flash('error', $content['message']);
            return redirect()->back();
        }
    }

    /**
     * Get the current status of the Deliverect onboarding process.
     *
     * @param int $business_id
     * @return array
     */
    public function getCurrentBussinessStatus($business_id)
    {
        $endpoint = "/api/deliverect/{$business_id}/on-board-with-deliverect/status";

        $internalRequest = Request::create($endpoint, 'GET');
        $response = Route::dispatch($internalRequest);

        $content = $response->getOriginalContent();

        if (isset($content['success']) && $content['success'] === true) {
            return [
                'status' => true,
                'client_id' => $content['data']['client_id'] ?? '',
                'client_secret' => $content['data']['client_secret'] ?? '',
                'registration_url' => $content['data']['registration_url'] ?? '',
            ];
        }

        return [
            'status' => false,
            'client_id' => '',
            'client_secret' => '',
            'registration_url' => ''
        ];
    }

    public function sendProductsToDeliverect($products, $business_id, $location_id){

        $endpoint = "/api/deliverect/{$business_id}/{$location_id}/sync-products";
        $internalRequest = Request::create($endpoint, 'POST', [
            'selected_rows' => $products
        ]);

        $response = Route::dispatch($internalRequest);
        $content = $response->getOriginalContent();
        // log the response
        \Log::info('Deliverect products sync response: ' . json_encode($content));

        return $response;
    }

    public function syncProducts(Request $request){
        
        $selectedRows = $request->input('selected_rows');
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id');
        $sendResponse = $this->sendProductsToDeliverect($selectedRows, $business_id, $location_id);
        

        // filter the products 
        return $sendResponse;
    }
}
