<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Category;
use App\CategoryLocation;
use App\Media;
use App\Product;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TaxonomyController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && !auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $modifer_set = Product::where('business_id', $business_id)
            ->where('type', 'modifier')
            ->select(DB::raw('name'), 'id')
            ->orderBy('name', 'asc')
            ->get();
        $modifiers_dropdown = $modifer_set->pluck('name', 'id');

        $locations_dropdown = BusinessLocation::forDropdown($business_id);

        $modifier_products = '';
        $modifier_products_ids = '';

        $category_locations_ids = [];
        $location_exists = CategoryLocation::get()->toArray();

        if (!empty($location_exists)) {
            foreach ($location_exists as $location) {
                $catlocID = $location['catlocID'] ?? "";
                $category_id = $location['category_id'] ?? "";
                $location_id = $location['location_id'] ?? "";
                $category_locations_ids[] = $location_id;

            }
        }
        /*  echo "<pre>";
        print_r($category_locations_ids);exit; */

        $Category_data = Category::where('category_type', $category_type)->where('business_id', $business_id)->get()->toArray();
        if (!empty($Category_data)) {
            foreach ($Category_data as $cat) {
                $CatID = $cat['id'] ?? "";
                $products = Product::where('category_id', $CatID)->get();
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $productID = $product['id'] ?? "";
                        $business_id = request()->session()->get('user.business_id');
                        $this_product = Product::where('business_id', $business_id)->find($productID);
                        // echo "<pre>";
                        // print_r($this_product);
                        $modifier_sets = $this_product->modifier_sets ?? "";
                        if (!empty($modifier_sets) && count($modifier_sets) > 0) {
                            if (count($this_product->modifier_sets) > 0) {
                                $product_ms = $this_product->modifier_sets;
                                $modifier_products = $product_ms;
                                $modifier_products_ids = $modifier_products->pluck('id');
                            }
                        }

                    }
                }
            }
        }
        /* echo "<pre>";
        print_r($modifier_products_ids); */

        // echo "<pre>";
        // print_r($modifier_products_ids);
        // exit();

        if (request()->ajax()) {

            // $category = Category::where('business_id', $business_id)
            //     ->where('category_type', $category_type)
            //     ->select(['name', 'image', 'short_code', 'description', 'id', 'parent_id', 'created_at', 'updated_at']);
            $category = Category::with('products')->select(array('categories.name', 'categories.image', 'categories.short_code', 'categories.id', 'categories.parent_id', 'categories.created_at', 'categories.updated_at'))
                ->where('category_type', $category_type)
                ->where('business_id', $business_id);

            // echo "<pre>";
            // print_r($category);
            // exit();
            return Datatables::of($category)
                ->addColumn(
                    'action',
                    '
                    <button data-href="{{action(\'TaxonomyController@edit\', [$id])}}?type=' . $category_type . '" class="btn btn-xs btn-primary edit_category_button"><i class="glyphicon glyphicon-edit"></i>  @lang("messages.edit")</button>
                        &nbsp;

                        <button data-href="{{action(\'TaxonomyController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_category_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    '
                )
                ->addColumn('productCount', function ($row) {
                    return '<a href="' . url('/products?categoryID=') . $row->id . '" class=""> ' . __('lang_v1.product') . ' (' . ($row->products->count()) . ')</button>';
                })
                ->addColumn('modifiersSet', function ($row) {
                    $products = Product::where('category_id', $row->id)->get();
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $productID = $product['id'] ?? "";
                            $business_id = request()->session()->get('user.business_id');
                            $this_product = Product::where('business_id', $business_id)->find($productID);
                            $modifier_sets = $this_product->modifier_sets ?? "";
                            if (count($modifier_sets) > 0) {
                                $modifiers = [];
                                foreach ($modifier_sets as $modifier) {
                                    $modifiers[] = $modifier->name;
                                }
                                return implode(', ', $modifiers);

                            }
                        }

                    }
                })

                ->addColumn(
                    'assign_unassign_locations',
                    '
                    <button data-href="{{action(\'TaxonomyController@loadlocationsTable\', [$id])}}" class="btn btn-outline-success mx-auto btn-md assign_unassign_locations_button"><i class="fas fa-plus"></i>  @lang("lang_v1.assign_unassign_locations")</button>
                    '
                )

                ->addColumn(
                    'assign_unassign_modifiers',
                    '
                    <button data-href="{{action(\'TaxonomyController@loadModifiersTable\', [$id])}}" class="btn btn-outline-success mx-auto btn-md assign_unassign_modifiers_button"><i class="fas fa-plus"></i>  @lang("lang_v1.assign_unassign_modifiers")</button>
                    '
                )

            // ->addColumn('assign_unassign_modifiers', function ($row) {
            //     // return ' <button type="button" class="btn btn-outline-success mx-auto btn-md" id="' . $row->id . '" data-toggle="modal" data-target="#addCategoryModifiersModal" onclick="myFunction(' . $row->id . ')"> ' . __('lang_v1.assign_unassign_modifiers') . ' <i class="fas fa-plus"></i></button>'
            //     return ' <button data-href="{{action(\'TaxonomyController@loadModifiersTable\', [$id])}}"  class="btn btn-outline-success mx-auto btn-md assign_unassign_modifiers_button"> ' . __('lang_v1.assign_unassign_modifiers') . ' <i class="fas fa-plus"></i></button>';
            // })

                ->editColumn('name', function ($row) {
                    if ($row->parent_id != 0) {
                        return '--' . $row->name;
                    } else {
                        return $row->name;
                    }
                })
                ->editColumn('image', function ($row) {
                    if ($row->image) {
                        $image_url = $this->getImageUrlAttribute($row->image);
                        return '<img src= "' . $image_url . '" height="50" />';
                    } else {
                        return '';
                    }

                })
                ->editColumn('created_at', function ($row) {
                    $cretaed_date = \Carbon\Carbon::parse($row->created_at)->format('F d Y, g:h:A ');
                    return $cretaed_date;
                })
                ->editColumn('updated_at', function ($row) {
                    $cretaed_date = \Carbon\Carbon::parse($row->updated_at)->format('F d Y, g:h:A ');
                    return $cretaed_date;
                })
                ->editColumn('short_code', function ($row) {
                    $short_code = "cat-" . $row->short_code;
                    return $short_code;
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns(['action', 'productCount', 'image', 'assign_unassign_locations', 'assign_unassign_modifiers'])
                ->make(true);
        }

        $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

        return view('taxonomy.index')->with(compact('module_category_data', 'module_category_data', 'modifer_set', 'modifiers_dropdown', 'modifier_products_ids', 'locations_dropdown', 'category_locations_ids'));
    }

    /**
     * Loads adModifiers add product modal.
     *
     * @return \Illuminate\Http\Response
     */
    public function addCategoryModifiers(Request $request)
    {
        if (request()->ajax()) {
            // try {
            $categoryID = request()->input('categoryID');
            $modifiers_ids = !empty(request()->input('modifiers_ids')) ? request()->input('modifiers_ids') : '';
            $products = Product::where('category_id', $categoryID)->get();
            DB::beginTransaction();
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            if (!empty($products)) {
                foreach ($products as $product) {
                    // echo "<pre>";
                    // print_r($product['id']);
                    $productID = $product['id'] ?? "";

                    $this_product = Product::where('business_id', $business_id)->find($productID);

                    // echo "<pre>";
                    // print_r($this_product);
                    if (count($this_product->modifier_sets) > 0) {
                        $product_ms = $this_product->modifier_sets;
                        $modifier_products = $product_ms;
                    }
                    if (!empty($modifier_products)) {
                        foreach ($modifier_products as $ids) {
                            $modifer_set = Product::where('business_id', $business_id)
                                ->where('id', $ids['id'])
                                ->where('type', 'modifier')
                                ->first();
                            DB::table('res_product_modifier_sets')->where('product_id', $productID)->where('modifier_set_id', $ids['id'])->delete();
                        }
                    }
                    if (!empty($modifiers_ids)) {
                        foreach ($modifiers_ids as $ids) {
                            $modifer_set = Product::where('business_id', $business_id)
                                ->where('id', $ids)
                                ->where('type', 'modifier')
                                ->first();

                            $products = [];
                            if (!empty($modifiers_ids)) {
                                $products = $productID;
                            }
                            //$modifer_set->modifier_products()->sync($products);
                            DB::table('res_product_modifier_sets')->insert(['modifier_set_id' => $ids, 'product_id' => $productID]);
                        }
                        // exit;
                    } else {
                        $this_product = Product::where('business_id', $business_id)->find($productID);
                        if (count($this_product->modifier_sets) > 0) {
                            $product_ms = $this_product->modifier_sets;
                            $modifier_products = $product_ms;
                        }
                        if (!empty($modifier_products)) {
                            foreach ($modifier_products as $ids) {
                                $modifer_set = Product::where('business_id', $business_id)
                                    ->where('id', $ids['id'])
                                    ->where('type', 'modifier')
                                    ->first();
                                $products[] = $categoryID;
                                DB::table('res_product_modifier_sets')->where('product_id', $productID)->where('modifier_set_id', $ids['id'])->delete();

                            }
                        }
                    }
                }
            }
            DB::commit();
            $output = ['success' => true, 'msg' => __("lang_v1.success")];

            // } catch (\Exception $e) {
            //     \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            //     $output = ['success' => false,
            //         'msg' => __("messages.something_went_wrong"),
            //     ];
            // }

            return $output;
        }

    }

    public function addCategoryLocations(Request $request)
    {
        if (request()->ajax()) {
            try {
                $categoryID = request()->input('categoryID');
                $locations_ids = !empty(request()->input('locations_ids')) ? request()->input('locations_ids') : '';
                $products = Product::where('category_id', $categoryID)->get();
                $business_id = request()->session()->get('user.business_id');
                $user_id = request()->session()->get('user.id');

                DB::beginTransaction();

                /*  echo "<pre>";
                print_r($locations_ids);exit; */
                /* $location_exists = CategoryLocation::where('category_id', $categoryID)->get(); */

                $location_exists = CategoryLocation::where('category_id', $categoryID)->get()->toArray();

                /* echo "<pre>";
                print_r($location_exists);exit;
                 */
                if (!empty($location_exists)) {
                    foreach ($location_exists as $location) {
                        // echo "<pre>";
                        // print_r($product['id']);
                        $catlocID = $location['catlocID'] ?? "";
                        $category_id = $location['category_id'] ?? "";
                        $location_id = $location['location_id'] ?? "";
                        DB::table('category_locations')->where('category_id', $category_id)->where('location_id', $location_id)->where('catlocID', $catlocID)->delete();
                    }
                }
                if (!empty($locations_ids)) {
                    foreach ($locations_ids as $ids) {
                        DB::table('category_locations')->insert(['category_id' => $categoryID, 'location_id' => $ids]);
                    }
                }
                DB::commit();
                $output = ['success' => true, 'msg' => __("lang_v1.success")];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong"),
                ];
            }

            return $output;
        }

    }

    public function getImageUrlAttribute($image)
    {

        if (!empty($image)) {
            $image_url = asset('/uploads/category_image/' . rawurlencode($image));
        } else {
            $image_url = asset('/img/default.png');
        }
        return $image_url;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && !auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->where('category_type', $category_type)
            ->select(['name', 'short_code', 'id'])
            ->get();

        $parent_categories = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $parent_categories[$category->id] = $category->name;
            }
        }

        return view('taxonomy.create')
            ->with(compact('parent_categories', 'module_category_data', 'category_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category_type = request()->input('category_type');
        if ($category_type == 'product' && !auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'short_code', 'category_type', 'description', 'image']);
            $input['image'] = $this->moduleUtil->uploadCategoryFile($request, 'image', config('constants.category_img_path'), 'image');
            if (!empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                $input['parent_id'] = $request->input('parent_id');
            } else {
                $input['parent_id'] = 0;
            }
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = $request->session()->get('user.id');

            $category = Category::create($input);
            if ($category->image) {
                $media_obj = new \App\Media([
                    'file_name' => $category->image,
                    'business_id' => $input['business_id'],
                    'description' => null,
                    'model_type' => Category::class,
                    'uploaded_by' => auth()->user()->id]);
                $category->media()->save($media_obj);
            }

            $output = ['success' => true,
                'data' => $category,
                'msg' => __("category.added_success"),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            $output = ['success' => false,
                'msg' => $msg,
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    public function loadlocationsTable($category_id)
    {
        if (!auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $business_id = request()->session()->get('user.business_id');
        $modifer_set = Product::where('business_id', $business_id)
            ->where('type', 'modifier')
            ->select(DB::raw('name'), 'id')
            ->orderBy('name', 'asc')
            ->get();
        $modifiers_dropdown = $modifer_set->pluck('name', 'id');
        $locations_dropdown = BusinessLocation::forDropdown($business_id);

        $modifier_products = '';
        $modifier_products_ids = '';
        $products = Product::where('category_id', $category_id)->get();
        if (!empty($products)) {
            foreach ($products as $product) {
                $productID = $product['id'] ?? "";
                $business_id = request()->session()->get('user.business_id');
                $this_product = Product::where('business_id', $business_id)->find($productID);
                $modifier_sets = $this_product->modifier_sets ?? "";
                if (count($modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $modifier_products = $product_ms;
                    $modifier_products_ids = $modifier_products->pluck('id');
                }
            }

        }

        $category_locations_ids = [];
        $location_exists = CategoryLocation::where('category_id', $category_id)->get()->toArray();
        /* echo "<pre>";
        print_r($location_exists); */

        if (!empty($location_exists)) {
            foreach ($location_exists as $location) {
                $catlocID = $location['catlocID'] ?? "";
                $category_id = $location['category_id'] ?? "";
                $location_id = $location['location_id'] ?? "";
                $category_locations_ids[] = $location_id;

            }
        }
        /*  echo "<pre>";
        print_r($category_locations_ids);exit; */

        return view('taxonomy.modifiers.add_locations_modal')->with(compact('modifer_set', 'modifiers_dropdown', 'category_id', 'modifier_products_ids', 'locations_dropdown', 'category_locations_ids'));
    }

    public function loadModifiersTable($category_id)
    {
        if (!auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $business_id = request()->session()->get('user.business_id');
        $modifer_set = Product::where('business_id', $business_id)
            ->where('type', 'modifier')
            ->select(DB::raw('name'), 'id')
            ->orderBy('name', 'asc')
            ->get();
        $modifiers_dropdown = $modifer_set->pluck('name', 'id');
        $modifier_products = '';
        $modifier_products_ids = '';
        $products = Product::where('category_id', $category_id)->get();
        if (!empty($products)) {
            foreach ($products as $product) {
                $productID = $product['id'] ?? "";
                $business_id = request()->session()->get('user.business_id');
                $this_product = Product::where('business_id', $business_id)->find($productID);
                $modifier_sets = $this_product->modifier_sets ?? "";
                if (count($modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $modifier_products = $product_ms;
                    $modifier_products_ids = $modifier_products->pluck('id');
                }
            }

        }
        /* echo "<pre>";
        print_r($modifier_products_ids);exit; */

        return view('taxonomy.modifiers.add_modifiers_modal')->with(compact('modifer_set', 'modifiers_dropdown', 'category_id', 'modifier_products_ids'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && !auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $category = Category::where('business_id', $business_id)->find($id);

            $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

            $parent_categories = Category::where('business_id', $business_id)
                ->where('parent_id', 0)
                ->where('category_type', $category_type)
                ->where('id', '!=', $id)
                ->pluck('name', 'id');
            $is_parent = false;

            if ($category->parent_id == 0) {
                $is_parent = true;
                $selected_parent = null;
            } else {
                $selected_parent = $category->parent_id;
            }

            return view('taxonomy.edit')->with(compact('category', 'parent_categories', 'is_parent', 'selected_parent', 'module_category_data'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'description', 'image']);
                $business_id = $request->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);
                $category->name = $input['name'];
                $category->description = $input['description'];

                if (!empty($request->input('short_code'))) {
                    $category->short_code = $request->input('short_code');
                }

                if (!empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                    $category->parent_id = $request->input('parent_id');
                } else {
                    $category->parent_id = 0;
                }
                //upload image
                $file_name = $this->moduleUtil->uploadCategoryFile($request, 'image', config('constants.category_img_path'), 'image');
                if (!empty($file_name)) {

                    //If previous image found then remove
                    if (!empty($category->image_path) && file_exists($category->image_path)) {
                        unlink($category->image_path);

                        $category->media()->delete();
                        $media_obj = new \App\Media([
                            'file_name' => $file_name,
                            'business_id' => $business_id,
                            'description' => null,
                            'model_type' => Category::class,
                            'uploaded_by' => auth()->user()->id]);
                        $category->media()->save($media_obj);
                    } else {
                        $media_obj = new \App\Media([
                            'file_name' => $file_name,
                            'business_id' => $business_id,
                            'description' => null,
                            'model_type' => Category::class,
                            'uploaded_by' => auth()->user()->id]);
                        $category->media()->save($media_obj);
                    }

                    $category->image = $file_name;
                }

                $category->save();

                $output = ['success' => true,
                    'msg' => __("category.updated_success"),
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

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);
                if (!empty($category->image_path) && file_exists($category->image_path)) {
                    unlink($category->image_path);
                    $category->media()->delete();
                }
                $category->delete();

                $output = ['success' => true,
                    'msg' => __("category.deleted_success"),
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

    public function getCategoriesApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');

            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $categories = Category::catAndSubCategories($api_settings->business_id);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($categories);
    }

    /**
     * get taxonomy index page
     * through ajax
     * @return \Illuminate\Http\Response
     */
    public function getTaxonomyIndexPage(Request $request)
    {
        if (request()->ajax()) {
            $category_type = $request->get('category_type');
            $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);
            //var_dump(module_category_data);exit;
            return view('taxonomy.ajax_index')->with(compact('module_category_data', 'category_type'));
        }
    }

    /**
     * @param $id
     */
    public function deleteCategoryMedia($id)
    {
        $media = Media::find($id);
        $category = Category::find($media->model_id);
        if (!empty($category->image_path)) {
            // unlink('/uploads/category_image/'.$category->image_path);
            $category->update([
                'image' => null,
            ]);
            $category->media()->delete();
        }

        if (\File::exists(public_path('uploads/category_image/' . $category->image_path))) {
            \File::delete(public_path('uploads/category_image/' . $category->image_path));
        }

        return response(['message' => 'Successfully Deleted.', 'success' => true]);
    }
}