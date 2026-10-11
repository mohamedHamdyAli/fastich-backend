@extends('layouts.app')
@section('title', __( 'report.stock_adjustment_report' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'report.stock_adjustment_report' )
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => action('StockAdjustmentController@index'), 'method' => 'get', 'id' =>
        'stock_adjustments_form' ]) !!}
        <div class="col-md-3 col-xs-6">
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
        <div class="col-md-3 col-xs-6 hide">
            <div class="form-group">
                {!! Form::label('location_id_old', __('purchase.business_location') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                    <select class="form-control select2" id="location_id_old">
                        @foreach($business_locations as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                'style' => 'width:100%']); !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('adjustment_type', __('stock_adjustment.adjustment_type') . ':')
                !!}
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
        {!! Form::close() !!}
        @endcomponent
    </div>
    <br>
    <div class="row">
        <div class="col-sm-6">
            @component('components.widget')
            <table class="table no-border">
                <tr>
                    <th>{{ __('report.total_normal') }}:</th>
                    <td>
                        <span class="total_normal">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.total_abnormal') }}:</th>
                    <td>
                        <span class="total_abnormal">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.total_wastage') }}:</th>
                    <td>
                        <span class="total_wastage">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.total_damage') }}:</th>
                    <td>
                        <span class="total_damage">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.total_shortage') }}:</th>
                    <td>
                        <span class="total_shortage">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.total_stock_adjustment') }}:</th>
                    <td>
                        <span class="total_amount">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
            </table>
            @endcomponent
        </div>

        <div class="col-sm-6">
            @component('components.widget')
            <table class="table no-border">
                <tr>
                    <th>{{ __('report.total_recovered') }}:</th>
                    <td>
                        <span class="total_recovered">
                            <i class="fas fa-sync fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
            </table>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            __('stock_adjustment.stock_adjustments')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="stock_adjustment_report_table">
                    <thead>
                        <tr>
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
        </div>
    </div>


</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
@endsection