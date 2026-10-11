@extends('layouts.app')
@section('title', __('lang_v1.sales_report_by_branch'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('lang_v1.sales_report_by_branch')}}</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'sales_report_by_branch_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location').':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.all'), 'required']); !!}
                        </div>
                    </div>
                </div>


                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sales_by_branch_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'sales_by_branch_date_filter', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-3 form-group">
                    {!! Form::label('sales_by_branch_start_time', __('lang_v1.time_range') . ':') !!}
                    @php
                        $startDay = Carbon::now()->startOfDay();
                        $endDay   = $startDay->copy()->endOfDay();
                    @endphp
                    <div class="input-group">
                        {!! Form::text('start_time', @format_time($startDay), [ 'class' => 'form-control width-50', 'id' => 'sales_by_branch_start_time']); !!}
                        {!! Form::text('end_time', @format_time($endDay), ['class' => 'form-control width-50', 'id' => 'sales_by_branch_end_time']); !!}
                    </div>
                </div>


                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <div class="tab-content">

                    <!-- Show product sell branch by branch -->
                    <div class="tab-pane active" id="sales_by_branch_tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped"
                            id="sales_by_branch_report_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>@lang('business.business_branch')</th>
                                        <th>@lang('business.business_reference')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.gross_sales')</th>
                                        <th>@lang('sale.gross_sales_percentage')</th>
                                        <th>@lang('report.discount_amount')</th>
                                        <th>@lang('sale.net_sales')</th>
                                        <th>@lang('report.taxes')</th>
                                        <th>@lang('sale.net_sales_exc_tax')</th>
                                        <th>@lang('sale.order_count')</th>
                                        <th>@lang('sale.average_order')</th>
                                        <th>@lang('report.total_unit_sold')</th>
                                        <th>@lang('sale.returned_quantity')</th>
                                          <th>@lang('sale.returned_amount')</th>

                                    </tr>
                                </thead>
                                <tfoot class="">
                                    <tr class="bg-gray font-17 footer-total">
                                        <td class="text-right" colspan="3"> <strong>@lang('sale.total'):</strong></td>
                                        <td><span class="display_currency" id="footer_sales_by_branch_report_gross_sales" ></span></td>
                                        <td><span class="display_currency" id="footer_sales_by_branch_report_gross_sales_percentage" ></span></td>
                                         <td><span class="display_currency" id="footer_sales_by_branch_report_discount_amount" ></span></td>
                                        <td><span class="display_currency" id="footer_sales_by_branch_report_net_sales" ></span></td>
                                        <td><span class="display_currency" id="footer_sales_by_branch_report_tax_amount" ></span></td>
                                        <td><span class="display_currency" id="footer_sales_by_branch_report_net_sales_without_tax" ></span></td>
                                        <td id="footer_sales_by_branch_report_order_count"></td>
                                        <td id="footer_sales_by_branch_report_average_order"></td>
                                        <td id="footer_total_sales_by_branch_report_sold"></td>
                                        <td id="footer_total_sales_by_branch_report_returned"></td>
                                        <td><span class="display_currency" id="footer_total_sales_by_branch_report_returned_amount" ></span></td>

                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')

    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>


@endsection
