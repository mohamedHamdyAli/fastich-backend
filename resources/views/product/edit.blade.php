@extends('layouts.app')
@section('title', __('product.edit_product'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.edit_product')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action('ProductController@update' , [$product->id] ), 'method' => 'PUT', 'id' =>
    'product_add_form',
    'class' => 'product_form', 'files' => true ]) !!}
    <input type="hidden" id="product_id" value="{{ $product->id }}">

    @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('name', __('product.product_name') . ':*') !!}
                {!! Form::text('name', $product->name, ['class' => 'form-control', 'required',
                'placeholder' => __('product.product_name')]); !!}
            </div>
        </div>

        {{--            <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">--}}
        {{--              <div class="form-group">--}}
        {{--                {!! Form::label('sku', __('product.sku')  . ':*') !!} @show_tooltip(__('tooltip.sku'))--}}
        {{--                {!! Form::text('sku', $product->sku, ['class' => 'form-control',--}}
        {{--                'placeholder' => __('product.sku'), 'required', 'readonly']); !!}--}}
        {{--              </div>--}}
        {{--            </div>--}}

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sku', __('product.sku') . ':*') !!} @show_tooltip(__('tooltip.sku'))
                {!! Form::text('sku', $product->sku, ['class' => 'form-control',
                'placeholder' => __('product.sku'), 'required']); !!}
            </div>
            <!-- hide this field and keep the default value -->
            <div class="form-group hide">
                {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
                {!! Form::select('barcode_type', $barcode_types, $product->barcode_type, ['placeholder' =>
                __('messages.please_select'), 'class' => ' form-control select2', 'required']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':*') !!}
                <div class="input-group">
                    {!! Form::select('unit_id', $units, $product->unit_id, ['placeholder' =>
                    __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
                    <span class="input-group-btn">
                        <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn
                            btn-default bg-white btn-flat quick_add_unit btn-modal"
                            data-href="{{action('UnitController@create', ['quick_add' => true])}}"
                            title="@lang('unit.add_unit')" data-container=".view_modal"><i
                                class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                </div>
            </div>
        </div>

        <div class="clearfix"></div>



        <div class="col-sm-4 @if(!session('business.enable_sub_units')) hide @endif">
            <div class="form-group">
                {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!}
                @show_tooltip(__('lang_v1.sub_units_tooltip'))

                <select name="sub_unit_ids[]" class="form-control select2" multiple id="sub_unit_ids">
                    @foreach($sub_units as $sub_unit_id => $sub_unit_value)
                    <option value="{{$sub_unit_id}}" @if(is_array($product->sub_unit_ids) &&in_array($sub_unit_id,
                        $product->sub_unit_ids)) selected
                        @endif
                        >{{$sub_unit_value['name']}}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
            <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                <div class="input-group">
                    {!! Form::select('brand_id', $brands, $product->brand_id, ['placeholder' =>
                    __('messages.please_select'), 'class' => 'form-control select2']); !!}
                    <span class="input-group-btn">
                        <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn
                            btn-default bg-white btn-flat btn-modal"
                            data-href="{{action('BrandController@create', ['quick_add' => true])}}"
                            title="@lang('brand.add_brand')" data-container=".view_modal"><i
                                class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                </div>
            </div>
        </div>



        <div class="clearfix"></div>
        <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
            <div class="form-group">
                {!! Form::label('category_id', __('product.category') . ':') !!}
                {!! Form::select('category_id', $categories, $product->category_id, ['placeholder' =>
                __('messages.please_select'), 'class' => 'form-control select2']); !!}
            </div>
        </div>

        <div
            class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
            <div class="form-group">
                {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                {!! Form::select('sub_category_id', $sub_categories, $product->sub_category_id, ['placeholder' =>
                __('messages.please_select'), 'class' => 'form-control select2']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('product_locations', __('business.business_locations') . ':') !!}
                @show_tooltip(__('lang_v1.product_location_help'))
                {!! Form::select('product_locations[]', $business_locations, $product->product_locations->pluck('id'),
                ['class' => 'form-control select2 Location', 'multiple', 'id' => 'product_locations']); !!}
            </div>
        </div>
        @if($kitchen_module_enabled)
        <div class="col-md-4" id="kitchenStations">
            @php
            $locationIds = $product->product_locations->pluck('id')->toArray();
            @endphp
            @foreach($kitchenStations as $key => $kitchenStation)
            <div id="kitchenStationsGroup-{{$key}}" @if($product->productKitchenStation->count() > 0)
                {{(in_array($key, $locationIds)) ? 'style=display:block;' : 'style=display:none;' }} @endif>
                <label>{{$kitchenStation->first()->location->name}}</label>
                @php
                $ids = $product->productKitchenStation()->pluck('kitchen_station_id')->toArray();
                @endphp
                @foreach($kitchenStation as $station)
                <div class="checkbox">
                    <label>
                        {!! Form::radio('kitchen_station['.$key.']', $station->id, (in_array($station->id, $ids)) ? true
                        : false,
                        [ 'class' => 'input-icheck']); !!} {{ $station->station_name }}
                    </label>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
        @endif
        <div class="clearfix"></div>
        @if($inventory_module_enabled)
        @can('product.add_recipe')
        <div class="col-sm-4">
            <div class="form-group">
                <br>
                <label>
                    {!! Form::checkbox('enable_stock', 1, $product->enable_stock, ['class' => 'input-icheck', 'id' =>
                    'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
                </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block">
                    <i>@lang('product.enable_stock_help')</i>
                </p>
            </div>
        </div>
        <div class="col-sm-4" id="alert_quantity_div" @if(!$product->enable_stock) style="display:none" @endif>
            <div class="form-group">
                {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!}
                @show_tooltip(__('tooltip.alert_quantity'))
                {!! Form::number('alert_quantity', $product->alert_quantity, ['class' => 'form-control',
                'placeholder' => __('product.alert_quantity') , 'min' => '0']); !!}
            </div>
        </div>
        @endcan
        @endif
        @if(!empty($common_settings['enable_product_warranty']))
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
                {!! Form::select('warranty_id', $warranties, $product->warranty_id, ['class' => 'form-control select2',
                'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        @endif
        <!-- include module fields -->
        @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
        @if(!empty($value['view_path']))
        @includeIf($value['view_path'], ['view_data' => $value['view_data']])
        @endif
        @endforeach
        @endif
        <div class="clearfix"></div>
        <!--  hide this HTML editor -->
        <div class="col-sm-8">
            <div class="form-group">
                {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
                {!! Form::textarea('product_description', $product->product_description, ['class' => 'form-control']);
                !!}
            </div>
        </div>
        <div class="col-sm-4 product-image">
            <div class="form-group">
                {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
                {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*']); !!}
                <small>
                    <p class="help-block">@lang('purchase.max_file_size', ['size' =>
                        (config('constants.document_size_limit') / 1000000)]).
                        @lang('lang_v1.aspect_ratio_should_be_1_1') @if(!empty($product->image)) <br>
                        @lang('lang_v1.previous_image_will_be_replaced') @endif</p>
                </small>
            </div>
        </div>

    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
        @if(session('business.enable_product_expiry'))

        @if(session('business.expiry_type') == 'add_expiry')
        @php
        $expiry_period = 12;
        $hide = true;
        @endphp
        @else
        @php
        $expiry_period = null;
        $hide = false;
        @endphp
        @endif
        <div class="col-sm-4 @if($hide) hide @endif">
            <div class="form-group">
                <div class="multi-input">
                    @php
                    $disabled = false;
                    $disabled_period = false;
                    if( empty($product->expiry_period_type) || empty($product->enable_stock) ){
                    $disabled = true;
                    }
                    if( empty($product->enable_stock) ){
                    $disabled_period = true;
                    }
                    @endphp
                    {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
                    {!! Form::text('expiry_period', @num_format($product->expiry_period), ['class' => 'form-control
                    pull-left input_number',
                    'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;', 'disabled' => $disabled]);
                    !!}
                    {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'),
                    '' =>__('product.not_applicable') ], $product->expiry_period_type, ['class' => 'form-control select2
                    pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type', 'disabled' => $disabled_period]);
                    !!}
                </div>
            </div>
        </div>
        @endif
        @if($enableModule->enable_product_description_imei_or_serial_number)
        <div class="col-sm-4">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('enable_sr_no', 1, $product->enable_sr_no, ['class' => 'input-icheck']); !!}
                    <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
                </label>
                @show_tooltip(__('lang_v1.tooltip_sr_no'))
            </div>
        </div>
        @endif
        <div class="col-sm-4">
            <div class="form-group">
                <br>
                <label>
                    {!! Form::checkbox('not_for_selling', 1, $product->not_for_selling, ['class' => 'input-icheck']);
                    !!} <strong>@lang('lang_v1.not_for_selling')</strong>
                </label> @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
            </div>
        </div>

        <div class="clearfix"></div>

        <!-- Rack, Row & position number -->
        @if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
        <div class="col-md-12">
            <h4>@lang('lang_v1.rack_details'):
                @show_tooltip(__('lang_v1.tooltip_rack_details'))
            </h4>
        </div>
        @foreach($business_locations as $id => $location)
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('rack_' . $id, $location . ':') !!}


                @if(!empty($rack_details[$id]))
                @if(session('business.enable_racks'))
                {!! Form::text('product_racks_update[' . $id . '][rack]', $rack_details[$id]['rack'], ['class' =>
                'form-control', 'id' => 'rack_' . $id]); !!}
                @endif

                @if(session('business.enable_row'))
                {!! Form::text('product_racks_update[' . $id . '][row]', $rack_details[$id]['row'], ['class' =>
                'form-control']); !!}
                @endif

                @if(session('business.enable_position'))
                {!! Form::text('product_racks_update[' . $id . '][position]', $rack_details[$id]['position'], ['class'
                => 'form-control']); !!}
                @endif
                @else
                {!! Form::text('product_racks[' . $id . '][rack]', null, ['class' => 'form-control', 'id' => 'rack_' .
                $id, 'placeholder' => __('lang_v1.rack')]); !!}

                {!! Form::text('product_racks[' . $id . '][row]', null, ['class' => 'form-control', 'placeholder' =>
                __('lang_v1.row')]); !!}

                {!! Form::text('product_racks[' . $id . '][position]', null, ['class' => 'form-control', 'placeholder'
                => __('lang_v1.position')]); !!}
                @endif

            </div>
        </div>
        @endforeach
        @endif
        @if($enableModule->enable_weight)
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('weight', __('lang_v1.weight') . ':') !!}
                {!! Form::text('weight', $product->weight, ['class' => 'form-control', 'placeholder' =>
                __('lang_v1.weight')]); !!}
            </div>
        </div>
        @endif
        <div class="clearfix"></div>
        @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $product_custom_field1 = !empty($custom_labels['product']['custom_field_1']) ?
        $custom_labels['product']['custom_field_1'] : __('lang_v1.product_custom_field1');
        $product_custom_field2 = !empty($custom_labels['product']['custom_field_2']) ?
        $custom_labels['product']['custom_field_2'] : __('lang_v1.product_custom_field2');
        $product_custom_field3 = !empty($custom_labels['product']['custom_field_3']) ?
        $custom_labels['product']['custom_field_3'] : __('lang_v1.product_custom_field3');
        $product_custom_field4 = !empty($custom_labels['product']['custom_field_4']) ?
        $custom_labels['product']['custom_field_4'] : __('lang_v1.product_custom_field4');
        @endphp
        <!--custom fields-->
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('product_custom_field1', $product_custom_field1 . ':') !!}
                {!! Form::text('product_custom_field1', $product->product_custom_field1, ['class' => 'form-control',
                'placeholder' => $product_custom_field1]); !!}
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('product_custom_field2', $product_custom_field2 . ':') !!}
                {!! Form::text('product_custom_field2', $product->product_custom_field2, ['class' => 'form-control',
                'placeholder' => $product_custom_field2]); !!}
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('product_custom_field3', $product_custom_field3 . ':') !!}
                {!! Form::text('product_custom_field3', $product->product_custom_field3, ['class' => 'form-control',
                'placeholder' => $product_custom_field3]); !!}
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('product_custom_field4', $product_custom_field4 . ':') !!}
                {!! Form::text('product_custom_field4', $product->product_custom_field4, ['class' => 'form-control',
                'placeholder' => $product_custom_field4]); !!}
            </div>
        </div>
        <!--custom fields-->
        @include('layouts.partials.module_form_part')
    </div>
    @endcomponent



    @component('components.widget', ['class' => 'box-primary'])
    <div class="row ">
        <div class="col-sm-6">
            <button type="button" class="btn btn-outline-success mx-auto btn-lg" data-toggle="modal"
                data-target="#addModifiersModal"> @lang('lang_v1.assign_unassign_modifiers') <i
                    class="fas fa-plus"></i></button>
        </div>
        <div class="col-sm-6">
            <p>
                <span> @lang('lang_v1.go_to_modifiers_page') </span>
                <a href="{{url('modules/modifiers')}}"> @lang('lang_v1.modifiers_page') </a>
            </p>
        </div>
        <div class="col-sm-12">
            <table class="table table-bordered table-striped" id="modifier_table">
                <thead>
                    <tr>
                        <th>@lang( 'restaurant.modifier_sets' )</th>
                        <th>@lang( 'restaurant.modifiers' )</th>

                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!--Custom Product Price Modal -->
    @include('product.modifiers.add_modifiers_modal')
    @endcomponent

    <!--Custom Product Price Modal -->
    @component('components.widget', ['class' => 'box-primary'])
    <div class="row ">
        <div class="col-sm-4">
            <button type="button" class="btn btn-outline-success mx-auto btn-lg" data-toggle="modal"
                data-target="#addCustomPriceModal"> @lang('lang_v1.add_custom_price') <i
                    class="fas fa-plus"></i></button>
        </div>
        <div class="col-sm-8"></div>

        <div class="col-sm-12">
            <table class="table table-bordered table-striped custom_price_table_class" id="custom_price_table">
                <thead>
                    <tr>
                        <th>@lang( 'business.business_locations' )</th>
                        <th>@lang( 'product.inc_of_tax' )</th>
                        <th>@lang( 'product.exc_of_tax' )</th>
                        <th>@lang( 'product.applicable_tax' )</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @include('product.partials.add_custom_price_modal')
    @endcomponent

    <!--Product Channel Price Level -->
    @component('components.widget', ['class' => 'box-primary'])
    <div class="row ">
        <div class="col-sm-4">
            <button type="button" class="btn btn-outline-success mx-auto btn-lg" data-toggle="modal"
                data-target="#addChannelPriceModal"> @lang('lang_v1.add_product_channel_price') <i
                    class="fas fa-plus"></i></button>
        </div>
        <div class="col-sm-8"></div>
        <div class="col-sm-12">
            <table class="table table-bordered table-striped channel_price_table_class" id="channel_price_table">
                <thead>
                    <tr>
                        <th>@lang( 'business.business_locations' )</th>
                         <th>@lang( 'lang_v1.channel_name' )</th>
                        <th>@lang( 'product.inc_of_tax' )</th>
                        <th>@lang( 'product.exc_of_tax' )</th>
                        <th>@lang( 'product.applicable_tax' )</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    @include('product.partials.add_channel_price_modal')
    @endcomponent

    <!--Custom Product Recipe Modal -->
    @if($inventory_module_enabled)
    @can('product.add_recipe')
    @component('components.widget', ['class' => 'box-primary'])
    <div class="row ">
        <div class="col-sm-12" id="ingredients_row_table" @if($product->enable_stock) style="display:none" @endif>

            <div class="col-sm-6">
                <button type="button" class="btn btn-outline-success mx-auto btn-lg" data-toggle="modal"
                    data-target="#addIngredintModal"> @lang('lang_v1.add_recipe') | @lang('lang_v1.edit_recipe') <i
                        class="fas fa-plus"></i></button>
            </div>
            @if($recipe)
            <div class="col-sm-3">
                <h4 class="modal-title" id="modalTitle">{{$recipe->variation->full_name}}</h4>
            </div>
            <div class="col-sm-3">
                <button class="btn btn-sm btn-danger delete_recipe"
                    data-href="{{ action('Restaurant\ModifierSetsController@destroyRecipe', [$recipe->id]) }}">
                    <i class="fa fa-trash"></i> @lang( 'messages.delete' )
                </button>
            </div>
            <div class="col-sm-12">
                @include('product.recipe.show')
            </div>
            @endif
            @include('product.recipe.add_ingredients')
        </div>

    </div>
    @endcomponent
    @endcan
    @endif

    @component('components.widget', ['class' => 'box-primary'])
    <div class="row ">
        <!-- hide this two field: this will affect default selection, please make VAT the default selection, and tax type Inclusive  .please note that you need to pre-define VAT as a default TAX type, as it is not pre-defined once a new account is created (new business created) -->
        <div class=" col-sm-4 @if(!session('business.enable_price_tax'))  @endif">
            <div class="form-group ">
                {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
                {!! Form::select('tax', $taxes, $product->tax, ['placeholder' => __('messages.please_select'), 'class'
                => 'form-control select2'], $tax_attributes); !!}
            </div>
        </div>

        <div class=" col-sm-4 @if(!session('business.enable_price_tax'))  @endif">
            <div class="form-group hide ">
                {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':*') !!}
                {!! Form::select('tax_type',['inclusive' => __('product.inclusive'), 'exclusive' =>
                __('product.exclusive')], $product->tax_type,
                ['class' => 'form-control select2', 'required']); !!}
            </div>
        </div>


        <div class="col-sm-4">
            <div class="form-group hide">
                {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
                {!! Form::select('type', $product_types, $product->type, ['class' => 'form-control select2',
                'required','disabled', 'data-action' => 'edit', 'data-product_id' => $product->id ]); !!}
            </div>
        </div>

        <div class="form-group col-sm-12" id="product_form_part"></div>
        <input type="hidden" id="variation_counter" value="0">
        <input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">
    </div>
    @endcomponent

    <div class="row">
        <input type="hidden" name="submit_type" id="submit_type">
        <div class="col-sm-12">
            <div class="text-center">
                <div class="btn-group">
                    @if($selling_price_group_count)
                    <button type="submit" value="submit_n_add_selling_prices"
                        class="btn btn-warning submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
                    @endif
                    @if($inventory_module_enabled)
                    @can('product.add_edit_opening_stock')
                    <button type="submit" @if(empty($product->enable_stock)) disabled="true" @endif
                        id="opening_stock_button" value="update_n_edit_opening_stock" class="btn bg-purple
                        submit_product_form">@lang('lang_v1.update_n_edit_opening_stock')</button>
                    @endcan
                    @endif

                    <button type="submit" value="save_n_add_another"
                        class="btn bg-maroon submit_product_form">@lang('lang_v1.update_n_add_another')</button>

                    <button type="submit" value="submit"
                        class="btn btn-primary submit_product_form">@lang('messages.update')</button>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
<!-- /.content -->

<div class="modal fade" id="view_edit_product_price_modal" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="view_edit_product_channel_price_modal" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>



@endsection

@section('javascript')
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
$(document).ready(function() {
    __page_leave_confirmation('#product_add_form');
});

// $(function () {
var product_id = $('#product_id').val();
$('#save_modifiers').on('click', function() {
    var ids = $('#modifiers_ids').val();

    $.ajax({
        url: '/products/add_modifiers/' + product_id,
        data: {
            modifiers_ids: ids,
        },
        dataType: 'json',
        success: function(result) {
            if (result.success == true) {
                $('#addModifiersModal').modal('hide');
                toastr.success(result.msg);
                modifier_table.ajax.reload();
                //location.reload();
            } else {
                toastr.error(result.msg);
            }
        }
    });
});

$('#save_custom_price').on('click', function() {
    var custom_product_locations = $('#custom_product_locations').val();
    var add_single_dsp_inc_tax = $('#add_single_dsp_inc_tax').val();
    var add_single_dpp = $('#add_single_dpp').val();
    var add_applicable_tax = $('#add_applicable_tax').val();

    $.ajax({
        url: '/products/add_product_custom_price/' + product_id,
        data: {
            custom_product_locations: custom_product_locations,
            add_single_dsp_inc_tax: add_single_dsp_inc_tax,
            add_single_dpp: add_single_dpp,
            add_applicable_tax: add_applicable_tax,
        },
        dataType: 'json',
        success: function(result) {
            if (result.success == true) {
                $("#addCustomPriceModal").find(':input').val('');
                $('#addCustomPriceModal').modal('hide');
                $("#addCustomPriceModal").find("select").val('').end(); // Clear dropdown content
                $("ul.select2-selection__rendered").empty(); // Clear li content
                toastr.success(result.msg);
                custom_price_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
        }
    });
});


// save_channel_price level

$('#save_channel_price').on('click', function() {
    var channel_product_locations = $('#channel_product_locations').val();
    var channel_product_ids = $('#channel_product_ids').val();
    var add_channel_dsp_inc_tax = $('#add_channel_dsp_inc_tax').val();
    var add_channel_single_dpp = $('#add_channel_single_dpp').val();
    var add_channel_applicable_tax = $('#add_channel_applicable_tax').val();

    $.ajax({
        url: '/products/add_product_channel_price/' + product_id,
        data: {
            channel_product_locations: channel_product_locations,
            channel_product_ids: channel_product_ids,
            add_channel_dsp_inc_tax: add_channel_dsp_inc_tax,
            add_channel_single_dpp: add_channel_single_dpp,
            add_channel_applicable_tax: add_channel_applicable_tax,
        },
        dataType: 'json',
        success: function(result) {
            if (result.success == true) {
                $("#addChannelPriceModal").find(':input').val('');
                $('#addChannelPriceModal').modal('hide');
                $("#addChannelPriceModal").find("select").val('').end(); // Clear dropdown content
                $("ul.select2-selection__rendered").empty(); // Clear li content
                toastr.success(result.msg);
                channel_price_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
        }
    });
});

//Brands table
var modifier_table = $('#modifier_table').DataTable({
    searching: false,
    paging: false,
    info: false,
    processing: true,
    serverSide: true,
    ajax: '/products/loadModifiersTable/' + product_id,
    columnDefs: [{
        "targets": [1],
        "orderable": false,
        "searchable": false
    }],
    columns: [{
            data: 'name',
            name: 'name'
        },
        {
            data: 'variations',
            name: 'variations'
        },

    ],
});



var channel_price_table = $('#channel_price_table').DataTable({
    searching: false,
    paging: false,
    info: false,
    processing: true,
    serverSide: true,
    ajax: '/products/loadChannelProductPriceTable/' + product_id,
    columnDefs: [{
        "targets": [1],
        "orderable": false,
        "searchable": false
    }],
    columns: [{
            data: 'business_name',
            name: 'business_name'
        },
        {
            data: 'channel_name',
            name: 'channel_name'
        },
        {
            data: 'channel_price',
            name: 'channel_price'
        },
        {
            data: 'channel_price_exc_tax',
            name: 'channel_price_exc_tax'
        },
        {
            data: 'channel_price_applicable_tax',
            name: 'channel_price_applicable_tax'
        },
    ],
});

var custom_price_table = $('#custom_price_table').DataTable({
    searching: false,
    paging: false,
    info: false,
    processing: true,
    serverSide: true,
    ajax: '/products/loadCustomProductPriceTable/' + product_id,
    columnDefs: [{
        "targets": [1],
        "orderable": false,
        "searchable": false
    }],
    columns: [{
            data: 'business_name',
            name: 'business_name'
        },
        {
            data: 'custom_price',
            name: 'custom_price'
        },
        {
            data: 'custom_price_exc_tax',
            name: 'custom_price_exc_tax'
        },
        {
            data: 'custom_price_applicable_tax',
            name: 'custom_price_applicable_tax'
        },
        // { data: 'action', name: 'action'},
    ],
});

$('#single_dpp_inc_tax').on('input', function() {
    $('.span').text(this.value)
})
$('#modifier_table_wrapper').find('.dt-buttons').hide();
$('#custom_price_table_wrapper').find('.dt-buttons').hide();

// });

function myChannelFunction(product_id, business_id, channel_id) {
    $.ajax({
        url: '/products/editChannelProductPrice/' + product_id + '/' + business_id + '/' + channel_id,
        dataType: 'html',
        success: function(result) {
            toastr.success(result.msg);
            $('#view_edit_product_channel_price_modal').html(result).modal('show');
        }
    });

}
function myFunction(product_id, business_id) {

    $.ajax({
        url: '/products/editCustomProductPrice/' + product_id + '/' + business_id,
        dataType: 'html',
        success: function(result) {

            toastr.success(result.msg);
            $('#view_edit_product_price_modal').html(result).modal('show');
            //edit_custom_product_price_type_form();
            //__currency_convert_recursively($('#view_edit_product_price_modal'));

        }
    });

}

function edit_custom_product_price_type_form() {

    //Disable Stock management & Woocommmerce sync if type combo
    if ($('#type').val() == 'combo') {
        $('#enable_stock').iCheck('uncheck');
        $('input[name="woocommerce_disable_sync"]').iCheck('check');
    }
    // product image hide
    if ($('#type').val() == 'variable') {
        $('.product-image').hide();
    } else {
        $('.product-image').show();
    }

    var action = $('#type').attr('data-action');
    var product_id = $('#type').attr('data-product_id');
    $.ajax({
        method: 'POST',
        url: '/products/custom_product_price_form_part',
        dataType: 'html',
        data: {
            type: $('#type').val(),
            product_id: product_id,
            action: action
        },
        success: function(result) {
            if (result) {
                $('#edit_custom_product_price_form_part').html(result);
                toggle_dsp_input();
            }
        },
    });
}

$(document).on('click', '#save_edit_channel_price', function(e) {
    var channel_price = $('#channel_price').val();
    var channel_price_exc_tax = $('#channel_price_exc_tax').val();
    var channel_price_applicable_tax = $('#channel_price_applicable_tax').val();
    var edit_product_id = $('#edit_product_id').val();
    var edit_business_id = $('#edit_business_id').val();
    var edit_channel_id = $('#edit_channel_id').val();

    $.ajax({
        url: '/products/edit_product_channel_price/' + edit_product_id + '/' + edit_business_id + '/' + edit_channel_id,
        data: {
            channel_price: channel_price,
            channel_price_exc_tax: channel_price_exc_tax,
            channel_price_applicable_tax: channel_price_applicable_tax,

        },
        dataType: 'json',
        success: function(result) {
            if (result.success == true) {
                $('#view_edit_product_channel_price_modal').modal('hide');
                toastr.success(result.msg);
                channel_price_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
        }
    });
});

$(document).on('click', '#save_edit_custom_price', function(e) {
    var edit_single_dsp_inc_tax = $('#edit_single_dsp_inc_tax').val();
    var edit_single_dpp = $('#edit_single_dpp').val();
    var edit_applicable_tax = $('#edit_applicable_tax').val();
    var edit_product_id = $('#edit_product_id').val();
    var edit_business_id = $('#edit_business_id').val();

    $.ajax({
        url: '/products/edit_product_custom_price/' + edit_product_id + '/' + edit_business_id,
        data: {
            edit_single_dsp_inc_tax: edit_single_dsp_inc_tax,
            edit_single_dpp: edit_single_dpp,
            edit_applicable_tax: edit_applicable_tax,

        },
        dataType: 'json',
        success: function(result) {
            if (result.success == true) {
                $('#view_edit_product_price_modal').modal('hide');
                toastr.success(result.msg);
                custom_price_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
        }
    });
});

$(document).on('change', 'input#single_dpp', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var unitPrice = __read_number($('input#single_dpp'));
    var purchase_exc_tax = __read_number($('input#default_purchase_price'));
    var profit_percent = __read_number($('input#profit_percent'));
    /// for VAT calculation = = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Selling Price calculation = 1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var tax_applied = (unitPrice * taxPercent);
    var selling_price = (unitPrice * taxPercentAdd);
    selling_price = parseFloat(selling_price);
    var purchase_inc_tax = ((purchase_exc_tax) + (purchase_exc_tax * taxPercent));
    purchase_inc_tax = parseFloat(purchase_inc_tax);
    if (purchase_inc_tax) {
        profit_percent = __get_rate(purchase_inc_tax, selling_price);
    }
    __write_number($('input#applicable_tax'), tax_applied);
    __write_number($('input#single_dpp'), unitPrice);
    __write_number($('input#single_dsp_inc_tax'), selling_price);
    __write_number($('input#single_dsp'), selling_price);
    __write_number($('input#profit_percent'), profit_percent);
    __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax,false,8);
    __write_number($('input#default_purchase_price'), purchase_exc_tax,false,8);
});

$(document).on('change', 'input#add_single_dpp', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var unitPrice = __read_number($('input#add_single_dpp'));
    /// for VAT calculation = = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Selling Price calculation = 1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var tax_applied = (unitPrice * taxPercent);
    var selling_price = (unitPrice * taxPercentAdd);
    __write_number($('input#add_single_dsp_inc_tax'), selling_price);
    __write_number($('input#add_single_dpp'), unitPrice);
    __write_number($('input#add_applicable_tax'), tax_applied);
});

$(document).on('change', 'input#add_channel_single_dpp', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var unitPrice = __read_number($('input#add_channel_single_dpp'));
    /// for VAT calculation = = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Selling Price calculation = 1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var tax_applied = (unitPrice * taxPercent);
    var selling_price = (unitPrice * taxPercentAdd);
    __write_number($('input#add_channel_dsp_inc_tax'), selling_price);
    __write_number($('input#add_channel_single_dpp'), unitPrice);
    __write_number($('input#add_channel_applicable_tax'), tax_applied);
});

$(document).on('change', 'input#edit_single_dpp', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var unitPrice = __read_number($('input#edit_single_dpp'));
    var selling_price = __read_number($('input#edit_single_dsp_inc_tax'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    selling_price = (unitPrice * taxPercentAdd);
    var tax_applied = (unitPrice * taxPercent);
    __write_number($('input#edit_single_dsp_inc_tax'), selling_price);
    __write_number($('input#edit_single_dpp'), unitPrice);
    __write_number($('input#edit_applicable_tax'), tax_applied);
});
//If Purchase price Exc tax is changed
$(document).on('change', 'input#default_purchase_price', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#single_dsp_inc_tax'));
    var purchase_exc_tax = __read_number($('input#default_purchase_price'));
    var profit_percent = __read_number($('input#profit_percent'));

    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var purchase_inc_tax = ((purchase_exc_tax) + (purchase_exc_tax * taxPercent));
    purchase_inc_tax = parseFloat(purchase_inc_tax);
    if ((selling_price == '')) {
        selling_price = purchase_inc_tax;
    }
    var unit_price = (selling_price / taxPercentAdd);
    unit_price = parseFloat(unit_price);
    var tax_applied = (unit_price * taxPercent);
    if (purchase_inc_tax) {
        profit_percent = __get_rate(purchase_inc_tax, selling_price);
    }
    __write_number($('input#applicable_tax'), tax_applied);
    __write_number($('input#single_dpp'), unit_price);
    __write_number($('input#single_dsp'), selling_price);
    __write_number($('input#single_dsp_inc_tax'), selling_price);
    __write_number($('input#profit_percent'), profit_percent);
    __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax,false,8);
    __write_number($('input#default_purchase_price'), purchase_exc_tax,false,8);
});
//If Purchase price Inc tax is changed
$(document).on('change', 'input#single_dpp_inc_tax', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#single_dsp_inc_tax'));
    var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
    var profit_percent = __read_number($('input#profit_percent'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);

    var purchase_exc_tax = (purchase_inc_tax / taxPercentAdd);
    purchase_exc_tax = parseFloat(purchase_exc_tax);
    if ((selling_price == '')) {
        selling_price = purchase_inc_tax;
    }
    var unit_price = (selling_price / taxPercentAdd);
    unit_price = parseFloat(unit_price);
    var tax_applied = (unit_price * taxPercent);
    if (purchase_inc_tax) {
        profit_percent = __get_rate(purchase_inc_tax, selling_price);
    }
    __write_number($('input#applicable_tax'), tax_applied);
    __write_number($('input#single_dpp'), unit_price);
    __write_number($('input#single_dsp'), selling_price);
    __write_number($('input#single_dsp_inc_tax'), selling_price);
    __write_number($('input#profit_percent'), profit_percent);
    __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax,false,8);
    __write_number($('input#default_purchase_price'), purchase_exc_tax,false,8);
});

//If Selling price inc tax is changed
$(document).on('change', 'input#single_dsp_inc_tax', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#single_dsp_inc_tax'));
    var purchase_exc_tax = __read_number($('input#default_purchase_price'));
    var profit_percent = __read_number($('input#profit_percent'));

    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var unit_price = (selling_price / taxPercentAdd);
    unit_price = parseFloat(unit_price);
    var tax_applied = (unit_price * taxPercent);
    var purchase_inc_tax = ((purchase_exc_tax) + (purchase_exc_tax * taxPercent));
    purchase_inc_tax = parseFloat(purchase_inc_tax);
    if (purchase_inc_tax) {
        profit_percent = __get_rate(purchase_inc_tax, selling_price);
    }
    __write_number($('input#applicable_tax'), tax_applied);
    __write_number($('input#single_dpp'), unit_price);
    __write_number($('input#single_dsp'), selling_price);
    __write_number($('input#single_dsp_inc_tax'), selling_price);
    __write_number($('input#profit_percent'), profit_percent);
    __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax,false,8);
    __write_number($('input#default_purchase_price'), purchase_exc_tax,false,8);
});
$(document).on('change', 'input#add_single_dsp_inc_tax', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#add_single_dsp_inc_tax'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var unit_price = (selling_price / taxPercentAdd);
    var tax_applied = (unit_price * taxPercent);

    __write_number($('input#add_single_dsp_inc_tax'), selling_price);
    __write_number($('input#add_single_dpp'), unit_price);
    __write_number($('input#add_applicable_tax'), tax_applied);
});

$(document).on('change', 'input#add_channel_dsp_inc_tax', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#add_channel_dsp_inc_tax'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var unit_price = (selling_price / taxPercentAdd);
    var tax_applied = (unit_price * taxPercent);

    __write_number($('input#add_channel_dsp_inc_tax'), selling_price);
    __write_number($('input#add_channel_single_dpp'), unit_price);
    __write_number($('input#add_channel_applicable_tax'), tax_applied);
});




$(document).on('change', 'input#edit_single_dsp_inc_tax', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#edit_single_dsp_inc_tax'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var unit_price = (selling_price / taxPercentAdd);
    var tax_applied = (unit_price * taxPercent);

    __write_number($('input#edit_single_dsp_inc_tax'), selling_price);
    __write_number($('input#edit_single_dpp'), unit_price);
    __write_number($('input#edit_applicable_tax'), tax_applied);

});

$(document).on('change', 'input#channel_price', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var selling_price = __read_number($('input#channel_price'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var unit_price = (selling_price / taxPercentAdd);
    var tax_applied = (unit_price * taxPercent);
    __write_number($('input#channel_price'), selling_price);
    __write_number($('input#channel_price_exc_tax'), unit_price);
    __write_number($('input#channel_price_applicable_tax'), tax_applied);

});

$(document).on('change', 'input#channel_price_exc_tax', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var unitPrice = __read_number($('input#channel_price_exc_tax'));
    var selling_price = __read_number($('input#channel_price'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    selling_price = (unitPrice * taxPercentAdd);
    var tax_applied = (unitPrice * taxPercent);
    __write_number($('input#channel_price'), selling_price);
    __write_number($('input#channel_price_exc_tax'), unitPrice);
    __write_number($('input#channel_price_applicable_tax'), tax_applied);
});


//If tax rate is changed
$(document).on('change', 'select#tax', function() {
    if ($('select#type').val() == 'single') {
        var selling_price = __read_number($('input#single_dsp_inc_tax'));
        var purchase_exc_tax = __read_number($('input#default_purchase_price'));
        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        var profit_percent = __read_number($('input#profit_percent'));

        var tax_rate = $('select#tax').find(':selected').data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        /// for VAT calculation = 0.15
        var taxPercent = (tax_rate / 100);
        /// for Unit Price calculation  1.15
        var taxPercentAdd = ((tax_rate + 100) / 100);
        var unit_price = (selling_price / taxPercentAdd);
        var tax_applied = (unit_price * taxPercent);
        purchase_inc_tax = ((purchase_exc_tax) + (purchase_exc_tax * taxPercent));
        purchase_inc_tax = parseFloat(purchase_inc_tax);
        if (selling_price && purchase_inc_tax) {
            //profit_percent = __get_rate(purchase_inc_tax, selling_price);
            profit_percent = (((selling_price - purchase_inc_tax) / purchase_inc_tax) * 100);
        }
        __write_number($('input#applicable_tax'), tax_applied);
        __write_number($('input#single_dpp'), unit_price);
        __write_number($('input#single_dsp'), selling_price);
        __write_number($('input#single_dsp_inc_tax'), selling_price);
        __write_number($('input#profit_percent'), profit_percent);
        __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax,false,8);
        __write_number($('input#default_purchase_price'), purchase_exc_tax),false,8;
    }
});
$(document).on('change', 'input#profit_percent', function(e) {
    var tax_rate = $('select#tax').find(':selected').data('rate');
    tax_rate = tax_rate == undefined ? 0 : tax_rate;
    var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
    var purchase_exc_tax = __read_number($('input#default_purchase_price'));
    var profit_percent = __read_number($('input#profit_percent'));
    /// for VAT calculation = 0.15
    var taxPercent = (tax_rate / 100);
    /// for Unit Price calculation  1.15
    var taxPercentAdd = ((tax_rate + 100) / 100);
    var purchase_inc_tax_profit_percent = ((profit_percent * purchase_inc_tax) / 100);
    var selling_price = (purchase_inc_tax + purchase_inc_tax_profit_percent);
    var unit_price = (selling_price / taxPercentAdd);
    var tax_applied = (unit_price * taxPercent);
    __write_number($('input#applicable_tax'), tax_applied);
    __write_number($('input#single_dpp'), unit_price);
    __write_number($('input#single_dsp'), selling_price);
    __write_number($('input#single_dsp_inc_tax'), selling_price);
    __write_number($('input#profit_percent'), profit_percent);
});
$(document).on('click', 'a.delete-product-channel-price', function(e) {
    e.preventDefault();
    swal({
        title: LANG.sure,
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            var href = $(this).attr('href');
            $.ajax({
                method: "get",
                url: href,
                dataType: "json",
                success: function(result) {
                    if (result.success == true) {
                        $('#view_edit_product_channel_price_modal').modal('hide');
                        toastr.success(result.msg);
                        channel_price_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        }
    });
});

$(document).on('click', 'a.delete-product-custom-price', function(e) {
    e.preventDefault();
    swal({
        title: LANG.sure,
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            var href = $(this).attr('href');
            $.ajax({
                method: "get",
                url: href,
                dataType: "json",
                success: function(result) {
                    if (result.success == true) {
                        $('#view_edit_product_price_modal').modal('hide');
                        toastr.success(result.msg);
                        custom_price_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        }
    });
});
// Recipe Management
$('.ingredient-row-sortable').sortable({
    cursor: "move",
    handle: ".handle",
    update: function(event, ui) {
        $(this).children().each(function(index) {
            $(this).find('input.sort_order').val(++index)
        });
    }
});

$('body').ready(function() {
    $(document).on('input', '#search_product', function() {
        console.log($(this).val());
        initialize_search($(this));
    });
});
/*  if ($('#search_product').length) {
initialize_search($('#-'));
} */
/* if ($('.search_product').length) {
$('.search_product').each(function() {
initialize_search($(this));
});
} */


$(document).on('click', '#save_recipe', function(e) {
    var ingredients_length = $('.ingredients_table tbody .quantity').length;
    if (ingredients_length < 1) {
        toastr.error('@lang("manufacturing::lang.please_add_ingredients")');
        e.preventDefault();
        return false;
    } else {
        //var ingredients = $('input[name="ingredients[0][quantity]"]').val();
        var variation_id = $('#variation_id').val();
        var ingredients_cost = $('#ingredients_cost').val();
        //var ingredients = $("input[name='ingredients']").map(function(){return $(this).val();}).get();
        var ingredients = [];
        var obj = {};
        $("input[name^='ingredients").each(function(index, el) {
            //console.log( index + ": " + $(this).val() );
            //console.log( $(this).prop("name").toLowerCase() + ": " + $(this).val() );
            let arrayKey = $(this).prop("name").toLowerCase();
            let arrayKeyEnd = arrayKey.replace("ingredients[", "");
            let arrayKeyNew = arrayKeyEnd.replace(/.$/, "]");

            var keyValue = $(this).val();
            obj[arrayKeyNew] = keyValue;
            //ingredients.push({[arrayKeyNew]: keyValue});
            //ingredients.push({arrayKeyNew, keyValue});
        });
        // console.log(ingredients);
        $.ajax({
            type: 'post',
            url: '/manufacturing/store-product-ingredient',
            data: {
                variation_id: variation_id,
                ingredients_cost: ingredients_cost,
                ingredients: obj,

            },
            dataType: 'json',
            success: function(result) {
                if (result.success == true) {
                    var tax_rate = $('select#tax').find(':selected').data('rate');
                    tax_rate = tax_rate == undefined ? 0 : tax_rate;
                    /// for VAT calculation = 0.15
                    var taxPercent = (tax_rate / 100);
                    /// for Unit Price calculation  1.15
                    var taxPercentAdd = ((tax_rate + 100) / 100);
                    purchase_exc_tax = parseFloat(purchase_exc_tax);
                    var purchase_exc_tax = (ingredients_cost / taxPercentAdd);
                    ingredients_cost = parseFloat(ingredients_cost);
                    var profit_percent = __read_number($('input#profit_percent'));
                    var selling_price = __read_number($('input#single_dsp_inc_tax'));
                    if ($('#update_product_purchase_price').is(":checked")) {

                        if (ingredients_cost) {
                            profit_percent = __get_rate(ingredients_cost, selling_price);
                        }
                        __write_number($('input#profit_percent'), profit_percent);
                        __write_number($('input#single_dpp_inc_tax'), ingredients_cost,false,8);
                        __write_number($('input#default_purchase_price'), purchase_exc_tax,false,8);
                    }

                    $('#addIngredintModal').modal('hide');
                    toastr.success(result.msg);
                    $.get('{{action("ProductController@edit", [$product->id])}}', function(data) {
                        var newData = $("#ingredients_row_table", data)
                        $("#ingredients_row_table").html('');
                        $("#ingredients_row_table").html(newData);

                    });
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    }
});


$(document).on('change', '.quantity,.row_sub_unit_id, #sub_unit_id', function() {
    calculateRecipeTotal();
});

function initialize_search(element) {
    element.autocomplete({
        source: function(request, response) {
            $.getJSON(
                '/products/list-material', {
                    term: request.term,
                    not_for_selling: 1,
                    product_types: ['single', 'variable']
                },
                response
            );
        },
        minLength: 2,
        response: function(event, ui) {
            if (ui.content.length == 0) {
                toastr.error(LANG.no_products_found);
                $('input#search_product').select();
            }
        },
        select: function(event, ui) {
            addIngredientRow(ui.item.variation_id, $(this));
        },
    }).autocomplete('instance')._renderItem = function(ul, item) {

        var string = '<li>' + item.name;
        if (item.type == 'variable') {
            string += '-' + item.variation;
        }
        string +=
            ' (' +
            item.sub_sku +
            ')' +
            '</li>';
        return $(string).appendTo(ul);
    }
}
$(document).on('click', 'button.remove_ingredient', function() {
    element = $(this).closest('tbody.ingredient-row-sortable');
    $(this).closest('tr').remove();
    //set the order of ingredient
    $(element).children().each(function(index) {
        $(this).find('input.sort_order').val(++index)
    });
    calculateRecipeTotal();
});


function addIngredientRow(variation_id, search_element) {
    var row_index = parseInt($('#row_index').val());
    var ingredient_group = search_element.closest('.box').find('.ingredient_group');
    var row_ig_index = ingredient_group.length ? ingredient_group.data('ig_index') : '';
    var sort_order = ++(search_element.closest('.box').find('.ingredient-row-sortable').children().length);

    $.ajax({
        url: "/manufacturing/get-ingredient-row-product/" + variation_id + '?row_index=' + row_index +
            '&row_ig_index=' + row_ig_index + '&sort_order=' + sort_order,
        dataType: 'html',
        success: function(result) {
            search_element.closest('.box').find('table.ingredients_table tbody').append(result);
            calculateRecipeTotal();
            row_index++;
            $('#row_index').val(row_index);
        },
    });
}

function calculateRecipeTotal() {
    var total = 0;
    $('.ingredients_table tbody tr').each(function() {
        var line_unit_price = $(this).find('.ingredient_price').val();
        var quantity = __read_number($(this).find('.quantity'));
        var multiplier = 1;
        if ($(this).find('.row_sub_unit_id').length) {
            multiplier = parseFloat(
                $(this).find('.row_sub_unit_id')
                .find(':selected')
                .data('multiplier')
            );
        }

        var line_total = line_unit_price * quantity * multiplier;
        $(this).find('span.ingredient_price').text(line_total);
        total += line_total;
    });
    $('span#ingredients_cost_text').text(total);
   // $('span#ingredients_cost_text').text(__currency_trans_from_en(total, true));
    $('#ingredients_cost').val(total);
}

$(document).on('click', 'button.delete_recipe', function() {
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then(willDelete => {
        if (willDelete) {
            var href = $(this).data('href');
            var data = $(this).serialize();
            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        $.get('{{action("ProductController@edit", [$product->id])}}',
                            function(data) {
                                var newData = $("#ingredients_row_table", data)
                                $("#ingredients_row_table").html('');
                                $("#ingredients_row_table").html(newData);

                            });
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});
</script>
@endsection
