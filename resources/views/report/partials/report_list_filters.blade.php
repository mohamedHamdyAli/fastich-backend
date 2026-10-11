@if(empty($only) || in_array('report_list_filter_location_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_location_id', __('purchase.business_location') . ':') !!}

        {!! Form::select('report_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2',
        'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>
@endif

@if(empty($only) || in_array('report_list_filter_payment_status', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_payment_status', __('purchase.payment_status') . ':') !!}
        {!! Form::select('report_list_filter_payment_status', ['paid' => __('lang_v1.paid'),
        'returned' => __('lang_v1.returned'), 'partial' => __('lang_v1.partial')],
        null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif

@if(empty($only) || in_array('report_list_filter_payment_method', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_payment_method', __('purchase.payment_method') . ':') !!}
        {!! Form::select('report_list_filter_payment_method', $payment_method_filter, null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('report_list_filter_client_app', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_client_app', __('client_app.client_app_name') . ':') !!}
        {!! Form::select('report_list_filter_client_app', $client_app_filter, null, ['class' => 'form-control
        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('report_list_filter_customer_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_customer_id', __('contact.customer') . ':') !!}
        {!! Form::select('report_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style'
        => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('report_list_filter_date_range', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_date_range', __('report.date_range') . ':') !!}
        {!! Form::text('report_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class'
        => 'form-control', 'readonly']); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('report_start_time', $only))
<div class="col-md-3 form-group">
    {!! Form::label('report_start_time', __('lang_v1.time_range') . ':') !!}
    @php
    $startDay = Carbon::now()->startOfDay();
    $endDay = $startDay->copy()->endOfDay();
    @endphp
    <div class="input-group">
        {!! Form::text('start_time', @format_time($startDay), ['class' =>
        'form-control width-50', 'id' => 'report_start_time']); !!}
        {!! Form::text('end_time', @format_time($endDay), ['class' => 'form-control width-50 ', 'id' =>
        'report_end_time']); !!}
    </div>
</div>
@endif
@if((empty($only) || in_array('report_list_filter_created_by', $only)) && !empty($sales_representative))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('report_list_filter_created_by', __('report.user') . ':') !!}
        {!! Form::select('report_list_filter_created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' =>
        'width:100%']); !!}
    </div>
</div>
@endif
