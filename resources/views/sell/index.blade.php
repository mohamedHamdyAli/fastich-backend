@extends('layouts.app')
@section('title', __( 'lang_v1.all_sales'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang( 'sale.sells')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
    @include('sell.partials.sell_list_filters')
    @if($is_woocommerce)
    <div class="col-md-3">
        <div class="form-group">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('only_woocommerce_sells', 1, false,
                    [ 'class' => 'input-icheck', 'id' => 'synced_from_woocommerce']); !!}
                    {{ __('lang_v1.synced_from_woocommerce') }}
                </label>
            </div>
        </div>
    </div>
    @endif
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sales')])

    @if(auth()->user()->can('sell.add') )
    @slot('tool')
    <div class="box-tools">
        <a class="btn btn-block btn-primary" href="{{action('SellController@create')}}">
            <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    @endslot
    @endif
    @php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    @endphp
    <table class="table table-bordered table-striped ajax_view" id="sell_table" style="width: 100%;">
        <thead>
            <tr>
                <th>@lang('messages.action')</th>
                <th>@lang('messages.date')</th>
                <th>@lang('sale.invoice_no')</th>
                <th>@lang('sale.location')</th>
                <th>@lang('lang_v1.payment_method')</th>
                <th>@lang('sale.total_amount')</th>
                <th>@lang('sale.payment_status')</th>
                <th>@lang('lang_v1.sell_due')</th>
                <th>@lang('sale.return_reason')</th>
                <th>@lang('client_app.client_app')</th>
                <th>@lang('lang_v1.added_by')</th>
                <th>@lang('sale.customer_name')</th>
                <th>@lang('lang_v1.contact_no')</th>
                <th>@lang('sale.zatca_status')</th>
                <!--  <th>@lang('receipt.order_type_label')</th> -->
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr class="bg-gray font-17 footer-total text-center">
                <td></td>
                <td></td>
                <td></td>
                <td><strong>@lang('sale.total'):</strong></td>
                <td class="payment_method_count"></td>
                <td class="footer_sale_total"></td>
                <td class="footer_payment_status_count"></td>
                <td class="footer_sell_due"></td>
                <td></td>
                <td class="client_app_count"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <!--  <td></td> -->

            </tr>
        </tfoot>
    </table>

    @endcomponent
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

<!-- This will be printed -->
<!-- <section class="invoice print_section" id="receipt_section">
</section> -->

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    //Date range as a button
    dateRangeSettings.startDate = moment().subtract(1, 'days');
    dateRangeSettings.endDate = moment();


    //dateRangeSettings.timePicker = true;


    console.log(dateRangeSettings.startDate);
    console.log(moment_date_format);
    console.log(moment_time_format);

    $('#product_sr_start_time').datetimepicker({
        format: moment_time_format,
        ignoreReadonly: false,

    }).on('dp.change', function(ev) {
        sell_table.ajax.reload();
    });
    $('#product_sr_end_time').datetimepicker({
        format: moment_time_format,
        ignoreReadonly: false,
    }).on('dp.change', function(ev) {
        sell_table.ajax.reload();
    });

    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                moment_date_format));
            sell_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
    });

    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        aaSorting: [
            [1, 'desc']
        ],
        "ajax": {
            "url": "/sells",
            "data": function(d) {
                var start = '';
                var end = '';
                var start_time = $('#product_sr_start_time').val();
                var end_time = $('#product_sr_end_time').val();

                if ($('#sell_list_filter_date_range').val()) {
                    start = $('#sell_list_filter_date_range').data('daterangepicker').startDate
                        .format('YYYY-MM-DD');

                    start = moment(start + " " + start_time, "YYYY-MM-DD" + " " +
                        moment_time_format).format('YYYY-MM-DD HH:mm');

                    end = $('#sell_list_filter_date_range').data('daterangepicker').endDate
                        .format('YYYY-MM-DD');
                    end = moment(end + " " + end_time, "YYYY-MM-DD" + " " + moment_time_format)
                        .format('YYYY-MM-DD HH:mm');
                }
                d.start_date = start;
                d.end_date = end;

                /* if ($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate
                        .format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate
                        .format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                } */
                d.is_direct_sale = 1;
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.zatca_status = $('#sell_list_filter_zatca_status').val();
                d.payment_method = $('#sell_list_filter_payment_method').val();
                d.client_app = $('#sell_list_filter_client_app').val();
                d.created_by = $('#created_by').val();
                d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                d.service_staffs = $('#service_staffs').val();

                if ($('#shipping_status').length) {
                    d.shipping_status = $('#shipping_status').val();
                }

                @if($is_woocommerce)
                if ($('#synced_from_woocommerce').is(':checked')) {
                    d.only_woocommerce_sells = 1;
                }
                @endif

                if ($('#only_subscriptions').is(':checked')) {
                    d.only_subscriptions = 1;
                }

                d = __datatable_ajax_callback(d);
            }
        },

        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        columns: [{
                data: 'action',
                name: 'action',
                orderable: false,
                "searchable": false
            },
            {
                data: 'transaction_date',
                name: 'transaction_date'
            },
            {
                data: 'invoice_no',
                name: 'invoice_no'
            },
            {
                data: 'business_location',
                name: 'bl.name'
            },
            {
                data: 'payment_methods',
                name: 'payment_methods',
                orderable: false,
                "searchable": false
            },
            {
                data: 'final_total',
                name: 'final_total',
                "searchable": false
            },
            {
                data: 'payment_status',
                name: 'payment_status'
            },
            {
                data: 'sell_due',
                name: 'sell_due',
                "searchable": false
            },
            {
                data: 'return_reason',
                name: 'return_reason',
                orderable: false,
                "searchable": false
            },
            {
                data: 'client_app',
                orderable: false,
                "searchable": false
            },
            {
                data: 'added_by',
                name: 'u.first_name',
            },
            {
                data: 'name',
                name: 'contacts.name'
            },
            {
                data: 'mobile',
                name: 'contacts.mobile'
            },
            {
                data: 'reporting_status',
                orderable: false,
                "searchable": false
            },
            
        ],

        "fnDrawCallback": function(oSettings) {
            __currency_convert_recursively($('#sell_table'));
        },
        "footerCallback": function(row, data, start, end, display) {
            var footer_sale_total = 0;
            var footer_total_paid = 0;
            var footer_sell_due = 0;
            var footer_total_sell_return_due = 0;
            for (var r in data) {
                footer_sale_total += $(data[r].final_total).data('orig-value') ? parseFloat($(data[
                    r].final_total).data('orig-value')) : 0;
                footer_total_paid += $(data[r].total_paid).data('orig-value') ? parseFloat($(data[r]
                    .total_paid).data('orig-value')) : 0;
                footer_sell_due += $(data[r].sell_due).data('orig-value') ?
                    parseFloat($(data[r].sell_due).data('orig-value')) : 0;
                footer_total_sell_return_due += $(data[r].return_due).data('orig-value') ?
                    parseFloat($(data[r].return_due).data('orig-value')) : 0;
            }

            $('.footer_total_sell_return_due').html(__currency_trans_from_en(
                footer_total_sell_return_due));
            $('.footer_sell_due').html(__currency_trans_from_en(footer_sell_due));
            $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));
            $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
            $('.payment_method_count').html(__count_status(data, 'payment_methods'));
            $('.client_app_count').html(__count_status(data, 'client_app'));
        },
        createdRow: function(row, data, dataIndex) {
            $(row).find('td:eq(6)').attr('class', 'clickable_td');
        }

    });

    $(document).on('change',
        '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status,#sell_list_filter_zatca_status, #sell_list_filter_payment_method,#sell_list_filter_client_app, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status',
        function() {
            sell_table.ajax.reload();
        });
    @if($is_woocommerce)
    $('#synced_from_woocommerce').on('ifChanged', function(event) {
        sell_table.ajax.reload();
    });
    @endif

    $('#only_subscriptions').on('ifChanged', function(event) {
        sell_table.ajax.reload();
    });
});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
