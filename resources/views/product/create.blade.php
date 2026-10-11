@extends('layouts.app')
@section('title', __('product.add_new_product'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.add_new_product')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
@php
  $form_class = empty($duplicate_product) ? 'create' : '';
@endphp
{!! Form::open(['url' => action('ProductController@store'), 'method' => 'post',
    'id' => 'product_add_form','class' => 'product_form ' . $form_class, 'files' => true ]) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('name', __('product.product_name') . ':*') !!}
              {!! Form::text('name', !empty($duplicate_product->name) ? $duplicate_product->name : null, ['class' => 'form-control', 'required',
              'placeholder' => __('product.product_name')]); !!}
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('sku', __('product.sku') . ':') !!} @show_tooltip(__('tooltip.sku'))
            {!! Form::text('sku', null, ['class' => 'form-control',
              'placeholder' => __('product.sku')]); !!}
          </div>
           <!-- hide this field and keep the default value -->
           <div class="form-group hide">
            {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
              {!! Form::select('barcode_type', $barcode_types, !empty($duplicate_product->barcode_type) ? $duplicate_product->barcode_type : $barcode_default, ['class' => 'form-control select2', 'required']); !!}
          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('unit_id', __('product.unit') . ':*') !!}
            <div class="input-group">
              {!! Form::select('unit_id', $units, !empty($duplicate_product->unit_id) ? $duplicate_product->unit_id : session('business.default_unit'), ['class' => 'form-control select2', 'required']); !!}
              <span class="input-group-btn">
                <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action('UnitController@create', ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
              </span>
            </div>
          </div>
        </div>

      <div class="clearfix"></div>


        <div class="col-sm-4 @if(!session('business.enable_sub_units')) hide @endif">
          <div class="form-group">
            {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!} @show_tooltip(__('lang_v1.sub_units_tooltip'))

            {!! Form::select('sub_unit_ids[]', [], !empty($duplicate_product->sub_unit_ids) ? $duplicate_product->sub_unit_ids : null, ['class' => 'form-control select2', 'multiple', 'id' => 'sub_unit_ids']); !!}
          </div>
        </div>

        <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
          <div class="form-group">
            {!! Form::label('brand_id', __('product.brand') . ':') !!}
            <div class="input-group">
              {!! Form::select('brand_id', $brands, !empty($duplicate_product->brand_id) ? $duplicate_product->brand_id : null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
            <span class="input-group-btn">
                <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action('BrandController@create', ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
              </span>
            </div>
          </div>
        </div>

        <div class="clearfix"></div>

        <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
          <div class="form-group">
            {!! Form::label('category_id', __('product.category') . ':') !!}
              {!! Form::select('category_id', $categories, !empty($duplicate_product->category_id) ? $duplicate_product->category_id : null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
          </div>
        </div>

        <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
          <div class="form-group">
            {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
              {!! Form::select('sub_category_id', $sub_categories, !empty($duplicate_product->sub_category_id) ? $duplicate_product->sub_category_id : null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
          </div>
        </div>

        @php
          $default_location = null;
          if(count($business_locations) == 1){
            $default_location = array_key_first($business_locations->toArray());
          }
        @endphp
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
              {!! Form::select('product_locations[]', $business_locations, $default_location, ['class' => 'form-control select2 Location', 'multiple', 'id' => 'product_locations']); !!}
          </div>
        </div>
            @if($kitchen_module_enabled)
                <div class="col-md-4" id="kitchenStations">
                @foreach($kitchenStations as $key => $kitchenStation)
                    <div id="kitchenStationsGroup-{{$key}}" style="display: none">
                        <label>{{$kitchenStation->first()->location->name}}</label>
                        @foreach($kitchenStation as $station)
                            <div class="checkbox">
                                <label>
                                    {!! Form::radio('kitchen_station['.$key.']', $station->id, false,
                                    [ 'class' => 'input-icheck']); !!} {{ $station->station_name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                </div>
            @endif



        <div class="clearfix"></div>

        <div class="col-sm-4">
          <div class="form-group">
          <br>
            <label>
              {!! Form::checkbox('enable_stock', 0, !empty($duplicate_product) ? $duplicate_product->enable_stock : false, ['class' => 'input-icheck', 'id' => 'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
            </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block"><i>@lang('product.enable_stock_help')</i></p>
          </div>
        </div>
        <div class="col-sm-4 @if(!empty($duplicate_product) && $duplicate_product->enable_stock == 0) hide @endif" id="alert_quantity_div">
          <div class="form-group">
            {!! Form::label('alert_quantity',  __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
            {!! Form::number('alert_quantity', !empty($duplicate_product->alert_quantity) ? $duplicate_product->alert_quantity : null , ['class' => 'form-control',
            'placeholder' => __('product.alert_quantity'), 'min' => '0']); !!}
          </div>
        </div>
        @if(!empty($common_settings['enable_product_warranty']))
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
            {!! Form::select('warranty_id', $warranties, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
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
        <div class="col-sm-8 hide">
            <!--  hide this HTML editor -->
          <div class="form-group">
            {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
              {!! Form::textarea('product_description', !empty($duplicate_product->product_description) ? $duplicate_product->product_description : null, ['class' => 'form-control']); !!}
          </div>
        </div>
        <div class="col-sm-4 product-image">
          <div class="form-group">
            {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
            {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*']); !!}
            <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p></small>
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
                {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
                {!! Form::text('expiry_period', !empty($duplicate_product->expiry_period) ? @num_format($duplicate_product->expiry_period) : $expiry_period, ['class' => 'form-control pull-left input_number',
                  'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;']); !!}
                {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], !empty($duplicate_product->expiry_period_type) ? $duplicate_product->expiry_period_type : 'months', ['class' => 'form-control select2 pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type']); !!}
              </div>
            </div>
          </div>
        @endif

        @if($enableModule->enable_product_description_imei_or_serial_number)
            <div class="col-sm-4">
              <div class="form-group">
                <br>
                <label>
                  {!! Form::checkbox('enable_sr_no', 1, !(empty($duplicate_product)) ? $duplicate_product->enable_sr_no : false, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
                </label> @show_tooltip(__('lang_v1.tooltip_sr_no'))
              </div>
            </div>
        @endif

        <div class="col-sm-4">
          <div class="form-group">
            <br>
            <label>
              {!! Form::checkbox('not_for_selling', 1, !(empty($duplicate_product)) ? $duplicate_product->not_for_selling : false, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.not_for_selling')</strong>
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
                {!! Form::label('rack_' . $id,  $location . ':') !!}

                @if(session('business.enable_racks'))
                  {!! Form::text('product_racks[' . $id . '][rack]', !empty($rack_details[$id]['rack']) ? $rack_details[$id]['rack'] : null, ['class' => 'form-control', 'id' => 'rack_' . $id,
                    'placeholder' => __('lang_v1.rack')]); !!}
                @endif

                @if(session('business.enable_row'))
                  {!! Form::text('product_racks[' . $id . '][row]', !empty($rack_details[$id]['row']) ? $rack_details[$id]['row'] : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]); !!}
                @endif

                @if(session('business.enable_position'))
                  {!! Form::text('product_racks[' . $id . '][position]', !empty($rack_details[$id]['position']) ? $rack_details[$id]['position'] : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.position')]); !!}
                @endif
              </div>
            </div>
          @endforeach
        @endif
        @if($enableModule->enable_weight)
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('weight',  __('lang_v1.weight') . ':') !!}
                {!! Form::text('weight', !empty($duplicate_product->weight) ? $duplicate_product->weight : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.weight')]); !!}
              </div>
            </div>
        @endif
        @php
          $custom_labels = json_decode(session('business.custom_labels'), true);
          $product_custom_field1 = !empty($custom_labels['product']['custom_field_1']) ? $custom_labels['product']['custom_field_1'] : __('lang_v1.product_custom_field1');
          $product_custom_field2 = !empty($custom_labels['product']['custom_field_2']) ? $custom_labels['product']['custom_field_2'] : __('lang_v1.product_custom_field2');
          $product_custom_field3 = !empty($custom_labels['product']['custom_field_3']) ? $custom_labels['product']['custom_field_3'] : __('lang_v1.product_custom_field3');
          $product_custom_field4 = !empty($custom_labels['product']['custom_field_4']) ? $custom_labels['product']['custom_field_4'] : __('lang_v1.product_custom_field4');
        @endphp
        <!--custom fields-->
        <div class="clearfix"></div>
        <div class="col-sm-3">
          <div class="form-group">
            {!! Form::label('product_custom_field1',  $product_custom_field1 . ':') !!}
            {!! Form::text('product_custom_field1', !empty($duplicate_product->product_custom_field1) ? $duplicate_product->product_custom_field1 : null, ['class' => 'form-control', 'placeholder' => $product_custom_field1]); !!}
          </div>
        </div>

        <div class="col-sm-3">
          <div class="form-group">
            {!! Form::label('product_custom_field2',  $product_custom_field2 . ':') !!}
            {!! Form::text('product_custom_field2', !empty($duplicate_product->product_custom_field2) ? $duplicate_product->product_custom_field2 : null, ['class' => 'form-control', 'placeholder' => $product_custom_field2]); !!}
          </div>
        </div>

        <div class="col-sm-3">
          <div class="form-group">
            {!! Form::label('product_custom_field3',  $product_custom_field3 . ':') !!}
            {!! Form::text('product_custom_field3', !empty($duplicate_product->product_custom_field3) ? $duplicate_product->product_custom_field3 : null, ['class' => 'form-control', 'placeholder' => $product_custom_field3]); !!}
          </div>
        </div>

        <div class="col-sm-3">
          <div class="form-group">
            {!! Form::label('product_custom_field4',  $product_custom_field4 . ':') !!}
            {!! Form::text('product_custom_field4', !empty($duplicate_product->product_custom_field4) ? $duplicate_product->product_custom_field4 : null, ['class' => 'form-control', 'placeholder' => $product_custom_field4]); !!}
          </div>
        </div>
        <!--custom fields-->
        <div class="clearfix"></div>
        @include('layouts.partials.module_form_part')
      </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">

       <!-- hide this two field: this will affect default selection, please make VAT the default selection, and tax type Inclusive  .please note that you need to pre-define VAT as a default TAX type, as it is not pre-defined once a new account is created (new business created) -->

        <div class="col-sm-4 @if(!session('business.enable_price_tax'))  @endif">
          <div class="form-group">
            {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
              {!! Form::select('tax', $taxes, $applicable_tax_type_VAT, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes); !!}
          </div>
        </div>

        <div class="col-sm-4 @if(!session('business.enable_price_tax'))  @endif">
          <div class="form-group hide">
            {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':*') !!}
              {!! Form::select('tax_type', ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')],  'inclusive',
              ['class' => 'form-control select2', 'required']); !!}
          </div>
        </div>
          <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
            {!! Form::select('type', $product_types, !empty($duplicate_product->type) ? $duplicate_product->type : null, ['class' => 'form-control select2',
            'required', 'data-action' => !empty($duplicate_product) ? 'duplicate' : 'add', 'data-product_id' => !empty($duplicate_product) ? $duplicate_product->id : '0']); !!}
          </div>
        </div>
        <div class="clearfix"></div>



        <div class="form-group col-sm-12" id="product_form_part">
          @include('product.partials.single_product_form_part', ['profit_percent' => $default_profit_percent])
        </div>

        <input type="hidden" id="variation_counter" value="1">
        <input type="hidden" id="default_profit_percent"
          value="{{ $default_profit_percent }}">

      </div>
    @endcomponent
    <div class="row">
    <div class="col-sm-12">
      <input type="hidden" name="submit_type" id="submit_type">
      <div class="text-center">
      <div class="btn-group">
        @if($selling_price_group_count)
          <button type="submit" value="submit_n_add_selling_prices" class="btn btn-warning submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
        @endif

        @if($inventory_module_enabled)
          @can('product.add_edit_opening_stock')
          <button id="opening_stock_button" @if(!empty($duplicate_product) && $duplicate_product->enable_stock == 0) disabled @endif type="submit" value="submit_n_add_opening_stock" class="btn bg-purple submit_product_form">@lang('lang_v1.save_n_add_opening_stock')</button>
          @endcan
        @endif

        <button type="submit" value="save_n_add_another" class="btn bg-maroon submit_product_form">@lang('lang_v1.save_n_add_another')</button>

        <button type="submit" value="submit" class="btn btn-primary submit_product_form">@lang('messages.save')</button>
      </div>

      </div>
    </div>
  </div>
{!! Form::close() !!}

</section>
<!-- /.content -->

@endsection

@section('javascript')
  @php $asset_v = env('APP_VERSION'); @endphp
  <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            __page_leave_confirmation('#product_add_form');
            onScan.attachTo(document, {
                suffixKeyCodes: [13], // enter-key expected at the end of a scan
                reactToPaste: true, // Compatibility to built-in scanners in paste-mode (as opposed to keyboard-mode)
                onScan: function(sCode, iQty) {
                    $('input#sku').val(sCode);
                },
                onScanError: function(oDebug) {
                    console.log(oDebug);
                },
                minLength: 2,
                ignoreIfFocusOn: ['input', '.form-control']
                // onKeyDetect: function(iKeyCode){ // output all potentially relevant key events - great for debugging!
                //     console.log('Pressed: ' + iKeyCode);
                // }
            });
        });

      $(document).on('change', 'input#single_dpp', function (e) {
        var tax_rate = $('select#tax') .find(':selected').data('rate');
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
        var purchase_inc_tax = ((purchase_exc_tax)  + (purchase_exc_tax * taxPercent));
        purchase_inc_tax = parseFloat(purchase_inc_tax);
         if(purchase_inc_tax){
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
      //If Selling price inc tax is changed
      $(document).on('change', 'input#single_dsp_inc_tax', function (e) {
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
        var purchase_inc_tax = ((purchase_exc_tax)  + (purchase_exc_tax * taxPercent));
        purchase_inc_tax = parseFloat(purchase_inc_tax);
         if(purchase_inc_tax){
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
      //If Purchase price Exc tax is changed
      $(document).on('change', 'input#default_purchase_price', function (e) {
        var tax_rate = $('select#tax').find(':selected').data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var selling_price = __read_number($('input#single_dsp_inc_tax'));
        var purchase_exc_tax = __read_number($('input#default_purchase_price'));
        var profit_percent = __read_number($('input#profit_percent'));

        /// for VAT calculation = 0.15
        var taxPercent = (tax_rate / 100);
        /// for Unit Price calculation  1.15
        var taxPercentAdd = ((tax_rate + 100) / 100);
        var purchase_inc_tax = ((purchase_exc_tax)  + (purchase_exc_tax * taxPercent));
        purchase_inc_tax = parseFloat(purchase_inc_tax);
        if((selling_price =='')){
          selling_price = purchase_inc_tax;
        }
        var unit_price = (selling_price / taxPercentAdd);
        unit_price = parseFloat(unit_price);
        var tax_applied = (unit_price * taxPercent);
        if(purchase_inc_tax){
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
      $(document).on('change', 'input#single_dpp_inc_tax', function (e) {
        var tax_rate = $('select#tax').find(':selected').data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var selling_price = __read_number($('input#single_dsp_inc_tax'));
        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        var profit_percent = __read_number($('input#profit_percent'));
        /// for VAT calculation = 0.15
        var taxPercent = (tax_rate / 100);
        /// for Unit Price calculation  1.15
        var taxPercentAdd = ((tax_rate + 100) / 100);

        var purchase_exc_tax = (purchase_inc_tax /  taxPercentAdd);
        purchase_exc_tax = parseFloat(purchase_exc_tax);
        if((selling_price =='')){
          selling_price = purchase_inc_tax;
        }
        var unit_price = (selling_price / taxPercentAdd);
        unit_price = parseFloat(unit_price);
        var tax_applied = (unit_price * taxPercent);
        if(purchase_inc_tax){
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

      //If tax rate is changed
      $(document).on('change', 'select#tax', function () {
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
          purchase_inc_tax = ((purchase_exc_tax)  + (purchase_exc_tax * taxPercent));
          purchase_inc_tax = parseFloat(purchase_inc_tax);
          if(selling_price && purchase_inc_tax ){
            //profit_percent = __get_rate(purchase_inc_tax, selling_price);
            profit_percent = (((selling_price - purchase_inc_tax ) / purchase_inc_tax ) * 100);
          }
          __write_number($('input#applicable_tax'), tax_applied);
          __write_number($('input#single_dpp'), unit_price);
          __write_number($('input#single_dsp'), selling_price);
          __write_number($('input#single_dsp_inc_tax'), selling_price);
          __write_number($('input#profit_percent'), profit_percent);
          __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax,false,8);
          __write_number($('input#default_purchase_price'), purchase_exc_tax,false,8);
        }
      });

      $(document).on('change', 'input#profit_percent', function (e) {
        var tax_rate = $('select#tax').find(':selected').data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        var purchase_exc_tax = __read_number($('input#default_purchase_price'));
        var profit_percent = __read_number($('input#profit_percent'));
        /// for VAT calculation = 0.15
        var taxPercent = (tax_rate / 100);
        /// for Unit Price calculation  1.15
        var taxPercentAdd = ((tax_rate + 100) / 100);
        var purchase_inc_tax_profit_percent = ((profit_percent *  purchase_inc_tax) / 100);
        var selling_price = (purchase_inc_tax +  purchase_inc_tax_profit_percent);
        var unit_price = (selling_price / taxPercentAdd);
        var tax_applied = (unit_price * taxPercent);
        __write_number($('input#applicable_tax'), tax_applied);
        __write_number($('input#single_dpp'), unit_price);
        __write_number($('input#single_dsp'), selling_price);
        __write_number($('input#single_dsp_inc_tax'), selling_price);
        __write_number($('input#profit_percent'), profit_percent);
    });

    </script>
@endsection
