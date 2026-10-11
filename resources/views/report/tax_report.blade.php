@extends('layouts.app')
@section('title', __( 'report.tax_report' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'report.tax_report' )
        <small>@lang( 'report.tax_report_msg' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('tax_report_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('tax_report_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('tax_report_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('tax_report_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'tax_report_date_range', 'readonly']); !!}
                    </div>
                </div>

                <div class="col-md-3 form-group">
                    {!! Form::label('tax_report_start_time', __('lang_v1.time_range') . ':') !!}
                    @php
                    $startDay = Carbon::now()->startOfDay();
                    $endDay = $startDay->copy()->endOfDay();
                    @endphp
                    <div class="input-group">
                        {!! Form::text('start_time', @format_time($startDay), ['style' => __('lang_v1.select_a_date_range'), 'class' =>
                        'form-control width-50 ', 'id' => 'tax_report_start_time']); !!}
                        {!! Form::text('end_time', @format_time($endDay), ['class' => 'form-control width-50 ', 'id' =>
                        'tax_report_end_time']); !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>
    {{--<div class="row">
        <div class="col-md-4 col-sm-12">
            @component('components.widget')
                @slot('title')
                    {{ __('report.input_tax') }} @show_tooltip(__('tooltip.input_tax'))
                @endslot
                <div class="input_tax">
                    <i class="fas fa-sync fa-spin fa-fw"></i>
                </div>
            @endcomponent
        </div>

        <div class="col-md-4 col-sm-12">
            @component('components.widget')
                @slot('title')
                    {{ __('report.output_tax') }} @show_tooltip(__('tooltip.output_tax'))
                @endslot
                <div class="output_tax">
                    <i class="fas fa-sync fa-spin fa-fw"></i>
                </div>
            @endcomponent
        </div>

        <div class="col-md-4 col-sm-12">
            @component('components.widget')
                @slot('title')
                    {{ __('lang_v1.expense_tax') }} @show_tooltip(__('lang_v1.expense_tax_tooltip'))
                @endslot
                <div class="expense_tax">
                    <i class="fa fa-refresh fa-spin fa-fw"></i>
                </div>
            @endcomponent
        </div>
    </div>--}}

    <div class="row hide">
        <div class="col-xs-12">
            @component('components.widget')
                @slot('title')
                    {{ __('lang_v1.tax_overall') }} @show_tooltip(__('tooltip.tax_overall'))
                @endslot
                <h3 class="text-muted">
                    {{ __('lang_v1.output_tax_minus_input_tax') }}:
                    <span class="tax_diff">
                        <i class="fas fa-sync fa-spin fa-fw"></i>
                    </span>
                </h3>
            @endcomponent
        </div>
    </div>
    <div class="row no-print hide">
        <div class="col-sm-12">
            <button type="button" class="btn btn-primary pull-right"
            aria-label="Print" onclick="window.print();"
            ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
        </div>
    </div>




    <div class="row">
        <div class="col-md-12">
           <!-- Custom Tabs -->

            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                     <li class="active">
                        <a href="#input_tax_tab" data-toggle="tab" aria-expanded="true"><i class="fa fas fa-arrow-circle-up" aria-hidden="true"></i> @lang('report.sales_tax')</a>
                    </li>
                </ul>

                <div class="tab-content">
                     <div class="tab-pane active" id="input_tax_tab">
                        <table class="table table-bordered table-striped" id="input_tax_table" width="100%">
                            <thead>
                                <tr>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('business.business_branch')</th>
                                    <th>@lang('sale.total_amount')</th>
                                    <th>@lang('report.total_taxes')</th>
                                    <th>@lang('business.tax')</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 text-left footer-total">
                                    <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                    <td><span class="display_currency" id="total_sell_amount" data-currency_symbol ="true"></span></td>
                                    <td><span class="display_currency" id="total_tax_amount" data-currency_symbol ="true"></span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


</section>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        //Date range as a button
        dateRangeSettings.startDate = moment().subtract(6, 'days');
        dateRangeSettings.endDate = moment();
        $('#tax_report_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#tax_report_date_range').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
            }
        );
        $('#tax_report_start_time').datetimepicker({
            format: moment_time_format,
            ignoreReadonly: false,
        }) .on('dp.change', function (ev) {
           input_tax_table.ajax.reload();
        });
        $('#tax_report_end_time').datetimepicker({
            format: moment_time_format,
            ignoreReadonly: false,
        }) .on('dp.change', function (ev) {
           input_tax_table.ajax.reload();
        });

        input_tax_table = $('#input_tax_table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            aaSorting: [
                [1, 'desc']
            ],
            ajax: {
                url: '/reports/tax-details',
                data: function(d) {
                    d.type = 'sell';
                    d.location_id = $('#tax_report_location_id').val();
                    var start = '';
                    var end = '';
                    var start_time = $('#tax_report_start_time').val();
                    var end_time = $('#tax_report_end_time').val();

                    if ($('#tax_report_date_range').val()) {
                        start = $('#tax_report_date_range').data('daterangepicker').startDate
                            .format('YYYY-MM-DD');

                        start = moment(start + " " + start_time, "YYYY-MM-DD" + " " +
                            moment_time_format).format('YYYY-MM-DD HH:mm');

                        end = $('#tax_report_date_range').data('daterangepicker').endDate
                            .format('YYYY-MM-DD');
                        end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format)
                            .format('YYYY-MM-DD HH:mm');
                    }
                    d.start_date = start;
                    d.end_date = end;



                   /*  var start = $('input#tax_report_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    var end = $('input#tax_report_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end; */
                }
            },
            columns: [
                {
                    data: 'transaction_date',
                    name: 'transaction_date',

                },
                {
                data: 'business_location',
                name: 'bl.name',
                searchable: false
                },

                {
                    data: 'final_total',
                    name: 'final_total',
                    searchable: false
                },
                {
                    data: 'tax_amount',
                    name: 'tax_amount',
                    searchable: false
                },
                {
                    data: "tax_name",
                    name: 'tr.name',
                    searchable: false
                },

            ],
            fnDrawCallback: function(oSettings) {
                $('#total_sell_amount').text(
                    sum_table_col($('#input_tax_table'), 'final_total')
                );
                $("#total_tax_amount").text(
                    sum_table_col($('#input_tax_table'), "tax_amount")
                );

                __currency_convert_recursively($('#input_tax_table'));
            },
        });


        $('#tax_report_date_range, #tax_report_location_id').change( function(){
            if ($("#input_tax_tab").hasClass('active')) {
                input_tax_table.ajax.reload();
            }
        });
    });
</script>
@if(!empty($tax_report_tabs))
    @foreach($tax_report_tabs as $key => $tabs)
        @foreach ($tabs as $index => $value)
            @if(!empty($value['module_js_path']))
                @include($value['module_js_path'])
            @endif
        @endforeach
    @endforeach
@endif
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection
