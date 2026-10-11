@extends('layouts.app')
@section('title', __('stock_adjustment.stock_adjustments'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('stock_adjustment.stock_adjustments')
        <small></small>
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            {!! Form::open(['url' => action('StockAdjustmentController@index'), 'method' => 'get', 'id' =>
            'stock_adjustments_form' ]) !!}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('search_product', __('lang_v1.search_product') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-search"></i>
                        </span>
                        <input type="hidden" value="" id="variation_id">
                        {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product',
                        'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('category_id', __('product.category') . ':') !!}
                    {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'style' =>
                    'width:100%', 'id' => 'product_list_filter_category_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('adjustment_type', __('stock_adjustment.adjustment_type') . ':') !!}
                    {!! Form::select('adjustment_type', [ 'normal' =>
                    __('stock_adjustment.normal'), 'abnormal' =>
                    __('stock_adjustment.abnormal'),'wastage' => __('stock_adjustment.wastage'), 'damage' =>
                    __('stock_adjustment.damage'),'shortage' => __('stock_adjustment.shortage')], null,
                    ['class' =>'form-control select2' , 'placeholder' => __('lang_v1.all')] ); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('stock_adjustment_list_filter_date', __('report.date_range') . ':') !!}
                    {!! Form::text('stock_adjustment_list_filter_date', null, ['placeholder' =>
                    __('lang_v1.select_a_date_range'), 'class'
                    => 'form-control', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3 form-group">
                {!! Form::label('stock_adjustment_list_start_time', __('lang_v1.time_range') . ':') !!}
                @php
                $startDay = Carbon::now()->startOfDay();
                $endDay = $startDay->copy()->endOfDay();
                @endphp
                <div class="input-group">
                    {!! Form::text('start_time', @format_time($startDay), ['class' =>'form-control width-50', 'id' =>
                    'stock_adjustment_list_start_time']); !!}
                    {!! Form::text('end_time', @format_time($endDay), ['class' => 'form-control width-50', 'id' =>
                    'stock_adjustment_list_end_time']); !!}
                </div>
            </div>
            {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    @component('components.widget', ['class' => 'box-primary', 'title' => __('stock_adjustment.all_stock_adjustments')])
    @slot('tool')
    <div class="box-tools">
        <a class="btn btn-block btn-primary" href="{{action('StockAdjustmentController@create')}}">
            <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped ajax_view" id="stock_adjustment_table">
            <thead>
                <tr>
                    <th>@lang('messages.action')</th>
                    <th>@lang('messages.date')</th>
                    <th>@lang('purchase.ref_no')</th>
                    <th>@lang('business.location')</th>
                    <th>@lang('stock_adjustment.adjustment_type')</th>
                    <th>@lang('stock_adjustment.total_amount')</th>
                    <th>@lang('stock_adjustment.total_amount_recovered')</th>
                    <th>@lang('stock_adjustment.reason_for_stock_adjustment')</th>
                    <th>@lang('lang_v1.added_by')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
@endsection