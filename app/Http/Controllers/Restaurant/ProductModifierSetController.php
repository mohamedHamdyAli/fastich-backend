<?php

namespace App\Http\Controllers\Restaurant;

use App\Product;
use App\TaxRate;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ProductModifierSetController extends Controller
{

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $modifer_set = Product::where('business_id', $business_id)
                ->where('type', 'modifier')
                ->where('id', $id)
                ->with(['modifier_products'])
                ->first();

            return view('restaurant.product_modifier_set.edit')
                ->with(compact('modifer_set'));
        }
    }

    /**
     * Show the form for duplicate the specified resource.
     * @return Response
     */
    public function duplicate($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $modifer_set = Product::where('business_id', $business_id)
                ->where('type', 'modifier')
                ->where('id', $id)
                ->with(['modifier_products'])
                ->first();

            $modifer_set_copy = Product::where('business_id', $business_id)
                ->where('name', 'like', "%{$modifer_set['name']}%")
                ->where('type', 'modifier')
                ->select(DB::raw('name'), 'id')
                ->orderBy('name', 'asc')
                ->get();
            // echo "<pre>";
            // print_r($modifer_set_copy->count());exit;
            if (!empty($modifer_set_copy) && !empty($modifer_set_copy->count())) {
                $modifer_set_name = $modifer_set_copy['name'] ?? "";

                $modifer_set['name'] = $modifer_set['name'] . " " . $modifer_set_copy->count();

            }
            // echo "<pre>";
            // print_r($modifer_set);exit;

            $taxRates = TaxRate::where('business_id', $business_id)->get()->toArray();

            return view('restaurant.modifier_sets.duplicate')->with(compact('modifer_set', 'taxRates'));
        }
    }

    public function editProductpage($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            // $modifer_set = Product::where('business_id', $business_id)
            //     ->where('type', 'modifier')
            //     ->where('id', $id)
            //     ->with(['modifier_products'])
            //     ->first();

            $modifer_set = Product::where('business_id', $business_id)
                ->where('type', 'modifier')
                ->select(DB::raw('name'), 'id')
                ->orderBy('name', 'asc')
                ->get();
            $modifiers_dropdown = $modifer_set->pluck('name', 'id');

            return view('product.modifiers.add_modifiers_modal')
                ->with(compact('modifiers_dropdown'));
        }
    }

    /**
     * Add new product row
     * @return Response
     */
    public function product_row($product_id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $product = Product::where('business_id', $business_id)
                ->where('id', $product_id)
                ->first();

            return view('restaurant.product_modifier_set.product_row')
                ->with(compact('product'));
        }
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update($modifier_set_id, Request $request)
    {
        try {
            DB::beginTransaction();

            $input = $request->all();
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $modifer_set = Product::where('business_id', $business_id)
                ->where('id', $modifier_set_id)
                ->where('type', 'modifier')
                ->first();

            $products = [];
            if (!empty($input['products'])) {
                $products = $input['products'];
            }
            // echo "<pre>";
            // print_r($products);
            // exit;
            $modifer_set->modifier_products()->sync($products);

            DB::commit();

            $output = ['success' => 1, 'msg' => __("lang_v1.success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => __("messages.something_went_wrong")];
        }

        return $output;
    }

    public function add_selected_modifiers(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $selected = $request->input('selected');
        $index = $request->input('index');

        $quantity = $request->input('quantity', 1);

        $modifiers = Variation::whereIn('id', $selected)
            ->get();

        if (count($modifiers) > 0) {
            return view('restaurant.product_modifier_set.add_selected_modifiers')
                ->with(compact('modifiers', 'index', 'quantity'));
        } else {
            return '';
        }
    }
}
