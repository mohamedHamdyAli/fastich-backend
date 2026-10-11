@if(empty($only) || in_array('sell_list_filter_location_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_location_id', __('purchase.business_location') . ':') !!}

        {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2',
        'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>
@endif

@if(empty($only) || in_array('sell_list_filter_payment_status', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_payment_status', __('purchase.payment_status') . ':') !!}
        {!! Form::select('sell_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'),
        'returned' => __('lang_v1.returned'), 'partial' => __('lang_v1.partial')],
        null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif

@if(empty($only) || in_array('sell_list_filter_payment_method', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_payment_method', __('purchase.payment_method') . ':') !!}
        {!! Form::select('sell_list_filter_payment_method', $payment_method_filter, null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sell_list_filter_client_app', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_client_app', __('client_app.client_app_name') . ':') !!}
        {!! Form::select('sell_list_filter_client_app', $client_app_filter, null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sell_list_filter_customer_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_customer_id', __('contact.customer') . ':') !!}
        {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style'
        => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sell_list_filter_date_range', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
        {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class'
        => 'form-control', 'readonly']); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('product_sr_start_time', $only))
<div class="col-md-3 form-group">
    {!! Form::label('product_sr_start_time', __('lang_v1.time_range') . ':') !!}
    @php
    $startDay = Carbon::now()->startOfDay();
    $endDay = $startDay->copy()->endOfDay();
    @endphp
    <div class="input-group">
        {!! Form::text('start_time', @format_time($startDay), ['class' =>'form-control width-50', 'id' =>
        'product_sr_start_time']); !!}
        {!! Form::text('end_time', @format_time($endDay), ['class' => 'form-control width-50', 'id' =>
        'product_sr_end_time']); !!}
    </div>
</div>
@endif
@if((empty($only) || in_array('created_by', $only)) && !empty($sales_representative))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('created_by', __('report.user') . ':') !!}
        {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' =>
        'width:100%']); !!}
    </div>
</div>
@endif
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_zatca_status', __('sale.zatca_status') . ':') !!}
        {!! Form::select('sell_list_filter_zatca_status', ['REPORTED' =>'REPORTED','NOT_REPORTED' => 'NOT REPORTED', 'PASS' => 'PASS'],
        null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@if(empty($only) || in_array('sales_cmsn_agnt', $only))
@if(!empty($is_cmsn_agent_enabled))
<div class="col-md-3 hide">
    <div class="form-group">
        {!! Form::label('sales_cmsn_agnt', __('lang_v1.sales_commission_agent') . ':') !!}
        {!! Form::select('sales_cmsn_agnt', $commission_agents, null, ['class' => 'form-control select2', 'style' =>
        'width:100%']); !!}
    </div>
</div>
@endif
@endif
@if(empty($only) || in_array('service_staffs', $only))
@if(!empty($service_staffs))
<div class="col-md-3 hide">
    <div class="form-group">
        {!! Form::label('service_staffs', __('restaurant.service_staff') . ':') !!}
        {!! Form::select('service_staffs', $service_staffs, null, ['class' => 'form-control select2', 'style' =>
        'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@endif
@if(!empty($shipping_statuses))
<div class="col-md-3 hide">
    <div class="form-group">
        {!! Form::label('shipping_status', __('lang_v1.shipping_status') . ':') !!}
        {!! Form::select('shipping_status', $shipping_statuses, null, ['class' => 'form-control select2', 'style' =>
        'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('only_subscriptions', $only))
<div class="col-md-3">
    <div class="form-group">
        <div class="checkbox">
            <label>
                <br>
                {!! Form::checkbox('only_subscriptions', 1, false,
                [ 'class' => 'input-icheck', 'id' => 'only_subscriptions']); !!} {{ __('lang_v1.subscriptions') }}
            </label>
        </div>
    </div>
</div>
@endif





