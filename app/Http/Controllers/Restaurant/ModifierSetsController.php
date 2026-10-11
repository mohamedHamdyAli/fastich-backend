<?php
namespace App\Http\Controllers\Restaurant;

use App\Product;
use App\TaxRate;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Utils\ManufacturingUtil;

class ModifierSetsController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $mfgUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ManufacturingUtil $mfgUtil)
    {
        $this->productUtil     = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->mfgUtil         = $mfgUtil;

    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $modifer_set = Product::where('business_id', $business_id)
                ->where('type', 'modifier')
                ->with(['variations', 'modifier_products']);
            return \Datatables::of($modifer_set)
                ->addColumn(
                    'action',
                    '
                    @can("product.update")
                        <button type="button" data-href="{{action(\'Restaurant\ModifierSetsController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_modifier_button edit-product-modifier-modal" data-container=".modifier_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                        <button type="button" data-href="{{action(\'Restaurant\ProductModifierSetController@duplicate\', [$id])}}" class="btn btn-xs btn-info btn-modal  edit-product-modifier-modal " data-container=".modifier_modal"><i class="fa fa-cubes"></i> @lang("restaurant.duplicate_modifiers")</button>
                    &nbsp;
                    @endcan

                    @can("product.delete")
                        <button data-href="{{action(\'Restaurant\ModifierSetsController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_modifier_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan
                    '
                )
                ->editColumn('modifier_products', function ($row) {
                    $products = [];
                    foreach ($row->modifier_products as $product) {
                        $products[] = $product->name;
                    }
                    return implode(',  ', $products);
                })
                ->editColumn('variations', function ($row) {
                    $modifiers = [];
                    foreach ($row->variations as $modifier) {
                        $modifiers[] = $modifier->name;
                    }

                    return implode(', ', $modifiers);
                })
                ->removeColumn('id')
                ->escapeColumns(['action'])
                ->make(true);
        }

        $business_id = request()->session()->get('user.business_id');
        $taxRates    = TaxRate::where('business_id', $business_id)->get()->toArray();

        return view('restaurant.modifier_sets.index', compact('taxRates'));
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function getModifiers()
    {
        $business_id = request()->session()->get('user.business_id');
        $modifer_set = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->leftjoin('mfg_recipes as mfgr', 'variations.id', '=', 'mfgr.variation_id')
            ->where('p.business_id', $business_id)
            ->where('p.type', '=', 'modifier')
            ->where('p.is_inactive', 0)
            ->select(
                'mfgr.id as mfgr_id',
                'p.id as product_id',
                'p.name as modifier_set_name',
                'variations.name as modifier_name',
                'variations.id as varition_id',
                'p.name as product_name');

        $modifiers = $modifer_set->get();
        // return $modifiers;
        if (! empty($modifiers)) {
            $modifiers_recipe           = [];
            $modifier_set_previous_name = '';
            foreach ($modifiers as $key => $modfier) {
                $modifier_set_name = $modfier->modifier_set_name ?? "";

                if ($modifier_set_name == $modifier_set_previous_name) {
                    $modifiers_recipe[$key]['modifier_set_name'] = "";
                } else {
                    $modifiers_recipe[$key]['modifier_set_name'] = $modifier_set_name;
                }
                $modifier_set_previous_name = $modifier_set_name;

                $modifiers_recipe[$key]['mfgr_id']    = $modfier->mfgr_id ?? "";
                $modifiers_recipe[$key]['product_id'] = $modfier->product_id ?? "";

                $modifiers_recipe[$key]['modifier_name'] = $modfier->modifier_name ?? "";
                $modifiers_recipe[$key]['varition_id']   = $modfier->varition_id ?? "";
                $modifiers_recipe[$key]['product_name']  = $modfier->product_name ?? "";

            }
        }

        //return $modifiers_recipe;

        if (request()->ajax()) {
            $modifer_set = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->leftjoin('mfg_recipes as mfgr', 'variations.id', '=', 'mfgr.variation_id')
                ->where('p.business_id', $business_id)
                ->where('p.type', '=', 'modifier')
                ->where('p.is_inactive', 0)
                ->select(
                    'mfgr.id as mfgr_id',
                    'p.id as product_id',
                    'p.name as modifier_set_name',
                    'variations.name as modifier_name',
                    'variations.id as varition_id',
                    'p.name as product_name');
            $modifiers = $modifer_set->get();
            if (! empty($modifiers)) {
                $modifiers_recipe           = [];
                $modifier_set_previous_name = '';
                foreach ($modifiers as $key => $modfier) {
                    $modifier_set_name = $modfier->modifier_set_name ?? "";

                    if ($modifier_set_name == $modifier_set_previous_name) {
                        $modifiers_recipe[$key]['modifier_set_name'] = "";
                    } else {
                        $modifiers_recipe[$key]['modifier_set_name'] = $modifier_set_name;
                    }
                    $modifier_set_previous_name = $modifier_set_name;

                    $modifiers_recipe[$key]['mfgr_id']    = $modfier->mfgr_id ?? "";
                    $modifiers_recipe[$key]['product_id'] = $modfier->product_id ?? "";

                    $modifiers_recipe[$key]['modifier_name'] = $modfier->modifier_name ?? "";
                    $modifiers_recipe[$key]['varition_id']   = $modfier->varition_id ?? "";
                    $modifiers_recipe[$key]['product_name']  = $modfier->product_name ?? "";

                }
            }
            //return $modifiers_recipe;
            //$modifiers_recipe = (object) $modifiers_recipe;

            return \Datatables::of($modifiers_recipe)
                ->addColumn(
                    'action',
                    function ($row) {
                        $row  = (object) $row;
                        $html = '';
                        if (! empty($row->varition_id)) {
                            $html .= '<button  data-href="' . action('Restaurant\ModifierSetsController@editIngredients', [$row->varition_id]) . '" class="btn btn-xs btn-primary  edit_modifier_option_button edit-modifier-option-modal" data-container=".modifier_modal"><i class="fas fa-plus"></i>' . __("lang_v1.add_recipe") . '|' . __("lang_v1.edit_recipe") . '</button>';
                        }

                        if (! empty($row->mfgr_id)) {
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                            $html .= '<button data-href="' . action('Restaurant\ModifierSetsController@showIngredients', [$row->mfgr_id]) . '" class="btn btn-xs btn-info btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __("messages.view") . '</button>';
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                            $html .= '<button data-href="' . action('Restaurant\ModifierSetsController@destroyRecipe', [$row->mfgr_id]) . '" class="btn btn-xs btn-danger delete_recipe"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</button>';

                        }

                        return $html;
                    }
                )

                ->editColumn('modifier_set_name', function ($row) {
                    $row = (object) $row;
                    return $row->modifier_set_name;
                })

                ->editColumn('name', function ($row) {
                    $row = (object) $row;
                    return $row->modifier_name;
                })
                /* ->editColumn('name', function ($row) {
            $modifiers = [];
            $html = '';
            foreach ($row->variations as $modifier) {
            $html = '<td>' . $modifier->name . '</td>';
            }
            return $html;
            }) */
                ->removeColumn('id')
                ->escapeColumns(['action'])
                ->make(true);
        }

        $business_id = request()->session()->get('user.business_id');
        $taxRates    = TaxRate::where('business_id', $business_id)->get()->toArray();

        return view('restaurant.modifier_sets.modifiers_option', compact('taxRates'));
    }
    /**
     * Show the specified resource.
     * @return Response
     */
    public function showIngredients($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $recipe      = MfgRecipe::with(['variation', 'variation.product', 'variation.product_variation', 'variation.media', 'sub_unit', 'variation.product.unit'])
            ->findOrFail($id);
        $ingredients = $this->mfgUtil->getIngredientDetails($recipe, $business_id);
        //return response()->json(['success' => 1, 'recipe' => $recipe, 'ingredients' => $ingredients], 200);

        return view('product.recipe.modifier_show', compact('recipe', 'ingredients'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */

    public function editIngredients($id, Request $request)
    {

        $business_id = request()->session()->get('user.business_id');
        $variation   = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('product_variations as pv', 'pv.id', '=', 'variations.product_variation_id')
            ->leftjoin('units as u', 'u.id', '=', 'p.unit_id')
            ->where('p.business_id', request()->session()->get('user.business_id'))
            ->where('variations.id', $id)
            ->select('variations.name as product_name', 'p.type as product_type', 'variations.*', 'pv.name as product_variation_name', 'p.unit_id as unit_id', 'u.short_name as unit_name')
            ->first();

        $ingredients = [];
        $with        = [
            'ingredients' => function ($query) {
                $query->orderBy('sort_order', 'asc');
            },
            'ingredients.variation', 'ingredients.variation.product',
            'ingredients.variation.product.unit',
            'ingredients.variation.product_variation',
            'ingredients.sub_unit', 'ingredients.ingredient_group'];

        $recipe = MfgRecipe::where('variation_id', $variation->id)
            ->with($with)
            ->first();
        if ($recipe) {
            $ingredients_obj = $recipe->ingredients;
            foreach ($ingredients_obj as $ingredient) {
                if (empty($ingredient->variation)) {
                    continue;
                }
                $ingredient_sub_units = $this->transactionUtil->getSubUnits($business_id, $ingredient->variation->product->unit->id);
                $multiplier           = ! empty($ingredient->sub_unit_id) ? $ingredient->sub_unit->base_unit_multiplier : 1;
                if (empty($multiplier)) {
                    $multiplier = 1;
                }
                $temp = [
                    'id'                      => $ingredient->variation->id,
                    'dpp_inc_tax'             => $ingredient->variation->dpp_inc_tax,
                    'default_purchase_price'  => $ingredient->variation->default_purchase_price,
                    'quantity'                => $ingredient->quantity,
                    'multiplier'              => $multiplier,
                    'sub_units'               => $ingredient_sub_units,
                    'sub_unit_id'             => $ingredient->sub_unit_id,
                    'unit'                    => $ingredient->variation->product->unit->short_name,
                    'full_name'               => $ingredient->variation->full_name,
                    'waste_percent'           => ! empty($ingredient->waste_percent) ? $ingredient->waste_percent : 0,
                    'sort_order'              => $ingredient->sort_order,
                    'ingredient_line_id'      => empty($copy_recipe) ? $ingredient->id : null,
                    'mfg_ingredient_group_id' => $ingredient->mfg_ingredient_group_id,
                    'ingredient_group_name'   => ! empty($ingredient->ingredient_group->name) ? $ingredient->ingredient_group->name : '',
                    'ig_description'          => ! empty($ingredient->ingredient_group->description) ? $ingredient->ingredient_group->description : '',
                ];

                $ingredients[] = $temp;
            }

        }
        $total_production_cost = 0;
        return view('product.recipe.add_modifiers_ingredients')->with(compact('variation', 'total_production_cost', 'recipe', 'ingredients'));

    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */

    public function edit($id, Request $request)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $modifer_set = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['variations'])
                ->first();
            $business_id = request()->session()->get('user.business_id');
            $taxRates    = TaxRate::where('business_id', $business_id)->get()->toArray();

            return view('restaurant.modifier_sets.edit')->with(compact('modifer_set', 'taxRates'));

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }

    }
    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */

    public function getIngredients($id, Request $request)
    {
        $variation = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('product_variations as pv', 'pv.id', '=', 'variations.product_variation_id')
            ->join('units as u', 'u.id', '=', 'p.unit_id')
            ->where('p.business_id', request()->session()->get('user.business_id'))
            ->where('variations.product_id', $id)
            ->select('p.name as product_name', 'p.type as product_type', 'variations.*', 'pv.name as product_variation_name', 'p.unit_id as unit_id', 'u.short_name as unit_name')
            ->first();

        $ingredients = [];
        $with        = [
            'ingredients' => function ($query) {
                $query->orderBy('sort_order', 'asc');
            },
            'ingredients.variation', 'ingredients.variation.product',
            'ingredients.variation.product.unit',
            'ingredients.variation.product_variation',
            'ingredients.sub_unit', 'ingredients.ingredient_group'];

        $recipe = MfgRecipe::where('variation_id', $variation->id)
            ->with($with)
            ->first();
        if ($recipe) {
            $ingredients_obj = $recipe->ingredients;
            foreach ($ingredients_obj as $ingredient) {
                if (empty($ingredient->variation)) {
                    continue;
                }
                $ingredient_sub_units = $this->transactionUtil->getSubUnits($business_id, $ingredient->variation->product->unit->id);
                $multiplier           = ! empty($ingredient->sub_unit_id) ? $ingredient->sub_unit->base_unit_multiplier : 1;
                if (empty($multiplier)) {
                    $multiplier = 1;
                }
                $temp = [
                    'id'                      => $ingredient->variation->id,
                    'dpp_inc_tax'             => $ingredient->variation->dpp_inc_tax,
                    'default_purchase_price'  => $ingredient->variation->default_purchase_price,
                    'quantity'                => $ingredient->quantity,
                    'multiplier'              => $multiplier,
                    'sub_units'               => $ingredient_sub_units,
                    'sub_unit_id'             => $ingredient->sub_unit_id,
                    'unit'                    => $ingredient->variation->product->unit->short_name,
                    'full_name'               => $ingredient->variation->full_name,
                    'waste_percent'           => ! empty($ingredient->waste_percent) ? $ingredient->waste_percent : 0,
                    'sort_order'              => $ingredient->sort_order,
                    'ingredient_line_id'      => empty($copy_recipe) ? $ingredient->id : null,
                    'mfg_ingredient_group_id' => $ingredient->mfg_ingredient_group_id,
                    'ingredient_group_name'   => ! empty($ingredient->ingredient_group->name) ? $ingredient->ingredient_group->name : '',
                    'ig_description'          => ! empty($ingredient->ingredient_group->description) ? $ingredient->ingredient_group->description : '',
                ];

                $ingredients[] = $temp;
            }

        }

        $total_production_cost = 0;
        return view('product.recipe.add_ingredients')->with(compact('variation', 'total_production_cost', 'recipe', 'ingredients'));

    }

    /**
     * copyInsertion the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function createCopy($id, Request $request)
    {
        //echo $id . "here";exit;
        try {
            if (! auth()->user()->can('product.create')) {
                abort(403, 'Unauthorized action.');
            }

            $input = $request->all();

            // echo "<pre>";
            // print_r($modifer_set);exit;
            $business_id = $request->session()->get('user.business_id');
            $user_id     = $request->session()->get('user.id');

            $modifer_set_data = [
                'name'        => $input['name'],
                'type'        => 'modifier',
                'sku'         => ' ',
                'tax_type'    => 'inclusive',
                'business_id' => $business_id,
                'created_by'  => $user_id,
            ];

            DB::beginTransaction();
            $modifer_set = Product::create($modifer_set_data);

            $sku              = $this->productUtil->generateProductSku($modifer_set->id);
            $modifer_set->sku = $sku;
            $modifer_set->save();

            $modifers = [];
            foreach ($input['modifier_name'] as $key => $value) {
                $modifers[] = [
                    'value'                  => $value,
                    'default_purchase_price' => $input['modifier_price'][$key],
                    'dpp_inc_tax'            => $input['modifier_price'][$key],
                    'profit_percent'         => 0,
                    'default_sell_price'     => $input['modifier_price'][$key],
                    'sell_price_inc_tax'     => $input['modifier_price_inc_tax'][$key],
                    'tax_id'                 => $input['modifier_tax'][$key],
                    'tax_type'               => $input['modifier_tax_type'][$key],
                ];
            }

            $modifiers_data   = [];
            $modifiers_data[] = [
                'name'       => 'DUMMY',
                'variations' => $modifers,
            ];
            $this->productUtil->createVariableProductVariations($modifer_set->id, $modifiers_data);

            DB::commit();

            $output = ['success' => 1, 'msg' => __("lang_v1.added_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }

        return $output;

    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $taxRates    = TaxRate::where('business_id', $business_id)->get()->toArray();

        return view('restaurant.modifier_sets.create', compact('taxRates'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            if (! auth()->user()->can('product.create')) {
                abort(403, 'Unauthorized action.');
            }

            $input       = $request->all();
            $business_id = $request->session()->get('user.business_id');
            $user_id     = $request->session()->get('user.id');

            $modifer_set_data = [
                'name'        => $input['name'],
                'type'        => 'modifier',
                'sku'         => ' ',
                'tax_type'    => 'inclusive',
                'business_id' => $business_id,
                'created_by'  => $user_id,
            ];

            DB::beginTransaction();
            $modifer_set = Product::create($modifer_set_data);

            $sku              = $this->productUtil->generateProductSku($modifer_set->id);
            $modifer_set->sku = $sku;
            $modifer_set->save();

            $modifers = [];
            foreach ($input['modifier_name'] as $key => $value) {
                $modifers[] = [
                    'value'                  => $value,
                    'default_purchase_price' => $input['modifier_price'][$key],
                    'dpp_inc_tax'            => $input['modifier_price'][$key],
                    'profit_percent'         => 0,
                    'default_sell_price'     => $input['modifier_price'][$key],
                    'sell_price_inc_tax'     => $input['modifier_price_inc_tax'][$key],
                    'tax_id'                 => $input['modifier_tax'][$key],
                    'tax_type'               => $input['modifier_tax_type'][$key],
                ];
            }

            $modifiers_data   = [];
            $modifiers_data[] = [
                'name'       => 'DUMMY',
                'variations' => $modifers,
            ];
            $this->productUtil->createVariableProductVariations($modifer_set->id, $modifiers_data);

            DB::commit();

            $output = ['success' => 1, 'msg' => __("lang_v1.added_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }

        return $output;
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('restaurant.modifier_sets.show');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input       = $request->all();
            $business_id = $request->session()->get('user.business_id');
            $user_id     = $request->session()->get('user.id');

            $modifer_set = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->where('type', 'modifier')
                ->first();
            $modifer_set->update(['name' => $input['name']]);

            //Get the dummy product variation
            $product_variation = $modifer_set->product_variations()->first();

            $modifiers_data[$product_variation->id]['name'] = $product_variation->name;

            $variations_edit = [];
            $variations      = [];

            //Set existing variations
            if (! empty($input['modifier_name_edit'])) {
                $modifier_name_edit  = $input['modifier_name_edit'];
                $modifier_price_edit = $input['modifier_price_edit'];

                foreach ($modifier_name_edit as $key => $name) {
                    if (isset($modifier_price_edit[$key])) {
                        $variations_edit[$key]['value']                  = $name;
                        $variations_edit[$key]['default_purchase_price'] = $modifier_price_edit[$key];
                        $variations_edit[$key]['dpp_inc_tax']            = $modifier_price_edit[$key];
                        $variations_edit[$key]['default_sell_price']     = $modifier_price_edit[$key];
                        $variations_edit[$key]['sell_price_inc_tax']     = $input['modifier_price_inc_tax_edit'][$key];
                        $variations_edit[$key]['tax_id']                 = $input['modifier_tax_edit'][$key];
                        $variations_edit[$key]['tax_type']               = $input['modifier_tax_type_edit'][$key];
                        $variations_edit[$key]['profit_percent']         = 0;
                    }
                }
            }
            //Set new variations
            if (! empty($input['modifier_name'])) {
                foreach ($input['modifier_name'] as $key => $value) {
                    $variations[] = [
                        'value'                  => $value,
                        'default_purchase_price' => $input['modifier_price'][$key],
                        'dpp_inc_tax'            => $input['modifier_price'][$key],
                        'profit_percent'         => 0,
                        'default_sell_price'     => $input['modifier_price'][$key],
                        'sell_price_inc_tax'     => $input['modifier_price_inc_tax'][$key],
                        'tax_id'                 => $input['modifier_tax'][$key],
                        'tax_type'               => $input['modifier_tax_type'][$key],
                    ];
                }
            }

            //Update variations
            $modifiers_data[$product_variation->id]['variations_edit'] = $variations_edit;
            $modifiers_data[$product_variation->id]['variations']      = $variations;
            $this->productUtil->updateVariableProductVariations($modifer_set->id, $modifiers_data);

            DB::commit();

            $output = ['success' => 1, 'msg' => __("lang_v1.updated_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyRecipe($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $recipe = MfgRecipe::where('id', $id)
                ->delete();

            $output = ['success' => 1,
                'msg'                => __('lang_v1.deleted_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg'                => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id, Request $request)
    {

        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            $business_id = $request->session()->get('user.business_id');

            Product::where('business_id', $business_id)
                ->where('type', 'modifier')
                ->where('id', $id)
                ->delete();

            DB::commit();

            $output = ['success' => 1, 'msg' => __("lang_v1.deleted_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }

        return $output;
    }
}
