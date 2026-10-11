@extends('layouts.app')
@section('title', __('lang_v1.sell_payment_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('lang_v1.sell_payment_report')}}</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
    @include('report.partials.report_list_filters')
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="sell_payment_report_table">
            <thead>
                <tr>
                    <th>@lang('messages.date')</th>
                    <th>@lang('business.business_branch')</th>
                    <th>@lang('sale.amount')</th>
                    <th>@lang('lang_v1.payment_method')</th>
                    <th>@lang('purchase.payment_status')</th>
                    <th>@lang('client_app.client_app_name')</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                    <td colspan="2" class="text-right"><strong>@lang('sale.total'):</strong></td>
                    <td class="text-left"><span class="display_currency" id="footer_total_amount"
                            data-currency_symbol="true"></span></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {



    //Date range as a button
    dateRangeSettings.startDate = moment().subtract(6, 'days');
    dateRangeSettings.endDate = moment();
    //dateRangeSettings.timePicker = true;



    $('#report_start_time, #report_end_time').datetimepicker({
        format: moment_time_format,
        ignoreReadonly: true,
    }).on('dp.change', function(ev) {
        sell_payment_report.ajax.reload();
    });
    $('#report_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#report_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                moment_date_format));
            sell_payment_report.ajax.reload();
        }
    );
    $('#report_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#report_list_filter_date_range').val('');
        sell_payment_report.ajax.reload();
    });

    $(document).on('change',
        '#report_list_filter_location_id, #report_list_filter_customer_id, #report_list_filter_payment_status, #report_list_filter_payment_method,#report_list_filter_client_app, #report_list_filter_created_by',
        function() {
            sell_payment_report.ajax.reload();
        });

    sell_payment_report = $('#sell_payment_report_table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        aaSorting: [
            [1, 'desc']
        ],
        ajax: {
            url: '/reports/sell-payment-report',
            data: function(d) {
                var start = '';
                var end = '';
                var start_time = $('#report_start_time').val();
                var end_time = $('#report_end_time').val();
                if ($('#report_list_filter_date_range').val()) {
                    start = $('#report_list_filter_date_range').data('daterangepicker').startDate
                        .format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " +
                        moment_time_format).format('YYYY-MM-DD HH:mm');

                    end = $('#report_list_filter_date_range').data('daterangepicker').endDate
                        .format('YYYY-MM-DD');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format)
                        .format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;
                d.location_id = $('#report_list_filter_location_id').val();
                d.customer_id = $('#report_list_filter_customer_id').val();
                d.payment_status = $('#report_list_filter_payment_status').val();
                d.payment_method = $('#report_list_filter_payment_method').val();
                d.client_app = $('#report_list_filter_client_app').val();
                d.created_by = $('#report_list_filter_created_by').val();

                d = __datatable_ajax_callback(d);
            }
        },
        columns: [{
                data: 'transaction_date',
                name: 'transaction_date',
                searchable: false
            },
            {
                data: 'business_location',
                name: 'bl.name'

            },
            {
                data: 'final_total',
                name: 'final_total',
                searchable: false
            },
            {
                data: 'payment_methods',
                name: 'payment_methods',
                orderable: false,
                searchable: false,
            },
            {
                data: 'payment_status',
                name: 'payment_status',
                searchable: false,
            },

            {
                data: 'client_app',
                orderable: false,
                searchable: false
            },
        ],
        fnDrawCallback: function(oSettings) {
            var total_amount = sum_table_col($('#sell_payment_report_table'), 'paid-amount');
            $('#footer_total_amount').text(total_amount);
            __currency_convert_recursively($('#sell_payment_report_table'));
        },
    });
});
</script>
@endsection
