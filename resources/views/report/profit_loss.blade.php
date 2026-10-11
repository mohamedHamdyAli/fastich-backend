@extends('layouts.app')
@section('title', __( 'report.profit_loss' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'report.profit_loss' )
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="print_section"><h2>{{session()->get('business.name')}} - @lang( 'report.profit_loss' )</h2></div>

    <div class="row no-print">
        <div class="col-md-3 col-md-offset-7 col-xs-6">
            <div class="input-group">
                <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                 <select class="form-control select2" id="profit_loss_location_filter">
                    @foreach($business_locations as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-2 col-xs-6">
            <div class="form-group pull-right">
                <div class="input-group">
                  <button type="button" class="btn btn-primary" id="profit_loss_date_filter">
                    <span>
                      <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                    </span>
                    <i class="fa fa-caret-down"></i>
                  </button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div id="pl_data_div">

            <div class="col-xs-6">
                @component('components.widget')
                    <table id="left_side_table" class="table table-striped">
                        <tr>
                            <th>{{ __('report.opening_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_purchase_price'))</small>:</th>
                            <td>
                                <span data-profit-loss="opening_stock" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.opening_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_sale_price'))</small>:</th>
                            <td>
                                <span data-profit-loss="opening_stock_by_sp" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('home.total_purchase') }}:<br><small class="text-muted">(@lang('product.exc_of_tax'), @lang('sale.discount'))</small></th>
                            <td>
                                <span data-profit-loss="total_purchase" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.total_stock_adjustment') }}:</th>
                            <td>
                                <span data-profit-loss="total_adjustment" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.total_expense') }}:</th>
                            <td>
                                <span data-profit-loss="total_expense" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_purchase_shipping_charge') }}:</th>
                            <td>
                                <span data-profit-loss="total_purchase_shipping_charge" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_transfer_shipping_charge') }}:</th>
                            <td>
                                <span data-profit-loss="total_transfer_shipping_charges" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_sell_discount') }}:</th>
                            <td>
                                <span data-profit-loss="total_sell_discount" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_reward_amount') }}:</th>
                            <td>
                                <span data-profit-loss="total_reward_amount" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_sell_return') }}:</th>
                            <td>
                                <span data-profit-loss="total_sell_return" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                    </table>
                @endcomponent
        </div>

            <div class="col-xs-6">
                @component('components.widget')
                    <table id="right_side_table" class="table table-striped">
                        <tr>
                            <th>{{ __('report.closing_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_purchase_price'))</small>:</th>
                            <td>
                                <span data-profit-loss="closing_stock" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.closing_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_sale_price'))</small>:</th>
                            <td>
                                <span data-profit-loss="closing_stock_by_sp" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('home.total_sell') }}: <br>
                                <!-- sub type for total sales -->
                                <ul id="sell_by_type_list"></ul>
                                <small class="text-muted">
                                    (@lang('product.exc_of_tax'), @lang('sale.discount'))
                                </small>
                            </th>
                            <td>
                                <span data-profit-loss="total_sell" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_sell_shipping_charge') }}:</th>
                            <td>
                                <span data-profit-loss="total_sell_shipping_charge" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.total_stock_recovered') }}:</th>
                            <td>
                                 <span data-profit-loss="total_recovered" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_purchase_return') }}:</th>
                            <td>
                                 <span data-profit-loss="total_purchase_return" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_purchase_discount') }}:</th>
                            <td>
                                <span data-profit-loss="total_purchase_discount" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.total_sell_round_off') }}:</th>
                            <td>
                                <span data-profit-loss="total_sell_round_off" class="display_currency" data-currency_symbol="true">
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                            &nbsp;
                            </td>
                        </tr>

                    </table>
                @endcomponent
            </div>
            <br>
            <div class="col-xs-12">
                @component('components.widget')
                    <h3 class="text-muted mb-0">
                        {{ __('lang_v1.gross_profit') }}:
                        <span data-profit-loss="gross_profit" class="display_currency" data-currency_symbol="true">
                        </span>
                    </h3>
                    <small class="help-block">
                        (@lang('lang_v1.total_sell_price') - @lang('lang_v1.total_purchase_price'))
                        <span data-profit-loss="gross_profit_label"></span>
                    </small>

                    <h3 class="text-muted mb-0">
                        {{ __('report.net_profit') }}:
                        <span data-profit-loss="net_profit" class="display_currency" data-currency_symbol="true">
                        </span>
                    </h3>
                    <small class="help-block">@lang('lang_v1.gross_profit') + (@lang('lang_v1.total_sell_shipping_charge') + @lang('report.total_stock_recovered') + @lang('lang_v1.total_purchase_discount') + @lang('lang_v1.total_sell_round_off')
                    <span id="rsmd_add_to_net_profit">

                    </span>

                    ) <br> - ( @lang('report.total_stock_adjustment') + @lang('report.total_expense') + @lang('lang_v1.total_purchase_shipping_charge') + @lang('lang_v1.total_transfer_shipping_charge') + @lang('lang_v1.total_sell_discount') + @lang('lang_v1.total_reward_amount')
                    <span id="lsmd_add_to_net_profit"></span>

                    )
                </small>
                @endcomponent
            </div>


        </div>
    </div>


    <div class="row no-print">
        <div class="col-sm-12">
            <button type="button" class="btn btn-primary pull-right"
            aria-label="Print" onclick="window.print();"
            ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
        </div>
    </div>
    <br>
    <div class="row no-print">
         <div class="col-md-3 col-md-offset-7 col-xs-6">
            <div class="input-group">
                <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                 <select class="form-control select2" id="profit_tabs_location_filter">
                    @foreach($business_locations as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-2 col-xs-6">
            <div class="form-group pull-right">
                <div class="input-group">
                  <button type="button" class="btn btn-primary" id="profit_tabs_filter">
                    <span>
                      <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                    </span>
                    <i class="fa fa-caret-down"></i>
                  </button>
                </div>
            </div>
        </div>
    </div>
    <div class="row no-print">
        <div class="col-md-12">
           <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#profit_by_products" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes" aria-hidden="true"></i> @lang('lang_v1.profit_by_products')</a>
                    </li>

                    <li>
                        <a href="#profit_by_categories" data-toggle="tab" aria-expanded="true"><i class="fa fa-tags" aria-hidden="true"></i> @lang('lang_v1.profit_by_categories')</a>
                    </li>

                    <li>
                        <a href="#profit_by_brands" data-toggle="tab" aria-expanded="true"><i class="fa fa-diamond" aria-hidden="true"></i> @lang('lang_v1.profit_by_brands')</a>
                    </li>

                    <li>
                        <a href="#profit_by_locations" data-toggle="tab" aria-expanded="true"><i class="fa fa-map-marker" aria-hidden="true"></i> @lang('lang_v1.profit_by_locations')</a>
                    </li>

                    <li>
                        <a href="#profit_by_invoice" data-toggle="tab" aria-expanded="true"><i class="fa fa-file-alt" aria-hidden="true"></i> @lang('lang_v1.profit_by_invoice')</a>
                    </li>

                    <li>
                        <a href="#profit_by_date" data-toggle="tab" aria-expanded="true"><i class="fa fa-calendar" aria-hidden="true"></i> @lang('lang_v1.profit_by_date')</a>
                    </li>
                    <li>
                        <a href="#profit_by_customer" data-toggle="tab" aria-expanded="true"><i class="fa fa-user" aria-hidden="true"></i> @lang('lang_v1.profit_by_customer')</a>
                    </li>
                    <li>
                        <a href="#profit_by_day" data-toggle="tab" aria-expanded="true"><i class="fa fa-calendar" aria-hidden="true"></i> @lang('lang_v1.profit_by_day')</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="profit_by_products">
                        @include('report.partials.profit_by_products')
                    </div>

                    <div class="tab-pane" id="profit_by_categories">
                        @include('report.partials.profit_by_categories')
                    </div>

                    <div class="tab-pane" id="profit_by_brands">
                        @include('report.partials.profit_by_brands')
                    </div>

                    <div class="tab-pane" id="profit_by_locations">
                        @include('report.partials.profit_by_locations')
                    </div>

                    <div class="tab-pane" id="profit_by_invoice">
                        @include('report.partials.profit_by_invoice')
                    </div>

                    <div class="tab-pane" id="profit_by_date">
                        @include('report.partials.profit_by_date')
                    </div>

                    <div class="tab-pane" id="profit_by_customer">
                        @include('report.partials.profit_by_customer')
                    </div>

                    <div class="tab-pane" id="profit_by_day">

                    </div>
                </div>
            </div>
        </div>
    </div>


</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>

<script type="text/javascript">
    $(document).ready( function() {
        $('#profit_tabs_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#profit_tabs_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            $('.nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
        });
        $('#profit_tabs_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#profit_tabs_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
            $('.nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
        });

         $('#profit_tabs_location_filter').change(function () {
            $('.nav-tabs li.active').find('a[data-toggle="tab"]').trigger('shown.bs.tab');
          });

        profit_by_products_table = $('#profit_by_products_table').DataTable({
                processing: true,
                serverSide: true,
                "ajax": {
                    "url": "/reports/get-profit/product",
                    "data": function ( d ) {
                        d.location_id = $('#profit_tabs_location_filter').val();
                        d.start_date = $('#profit_tabs_filter').data('daterangepicker') .startDate.format('YYYY-MM-DD');
                        d.end_date = $('#profit_tabs_filter')  .data('daterangepicker') .endDate.format('YYYY-MM-DD');
                    }
                },
                columns: [
                    { data: 'product', name: 'P.name'  },
                    { data: 'gross_profit', "searchable": false},
                    { data: 'gross_profit_exclusive_VAT', "searchable": false},
                ],
                fnDrawCallback: function(oSettings) {
                    var total_profit = sum_table_col($('#profit_by_products_table'), 'gross-profit');
                     var total_profit_vat = sum_table_col($('#profit_by_products_table'), 'gross_profit_exclusive_VAT');
                    $('#profit_by_products_table .footer_total').text(total_profit);
                    $('#profit_by_products_table .footer_total_vat').text(total_profit_vat);

                    __currency_convert_recursively($('#profit_by_products_table'));
                },
            });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if ( target == '#profit_by_categories') {
                if(typeof profit_by_categories_datatable == 'undefined') {
                    profit_by_categories_datatable = $('#profit_by_categories_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/category",
                            "data": function ( d ) {
                                 d.location_id = $('#profit_tabs_location_filter').val();
                                d.start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'category', name: 'C.name'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_categories_table'), 'gross-profit');
                            $('#profit_by_categories_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_categories_table'));
                        },
                    });
                } else {
                    profit_by_categories_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_brands') {
                if(typeof profit_by_brands_datatable == 'undefined') {
                    profit_by_brands_datatable = $('#profit_by_brands_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/brand",
                            "data": function ( d ) {
                                d.location_id = $('#profit_tabs_location_filter').val();
                                d.start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'brand', name: 'B.name'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            d.location_id = $('#profit_tabs_location_filter').val();
                            var total_profit = sum_table_col($('#profit_by_brands_table'), 'gross-profit');
                            $('#profit_by_brands_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_brands_table'));
                        },
                    });
                } else {
                    profit_by_brands_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_locations') {
                if(typeof profit_by_locations_datatable == 'undefined') {
                    profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/location",
                            "data": function ( d ) {
                                d.location_id = $('#profit_tabs_location_filter').val();
                                d.start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'location', name: 'L.name'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_locations_table'), 'gross-profit');
                            $('#profit_by_locations_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_locations_table'));
                        },
                    });
                } else {
                    profit_by_locations_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_invoice') {
                if(typeof profit_by_invoice_datatable == 'undefined') {
                    profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/invoice",
                            "data": function ( d ) {
                                d.location_id = $('#profit_tabs_location_filter').val();
                                d.start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'invoice_no', name: 'sale.invoice_no'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_invoice_table'), 'gross-profit');
                            $('#profit_by_invoice_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_invoice_table'));
                        },
                    });
                } else {
                    profit_by_invoice_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_date') {
                if(typeof profit_by_date_datatable == 'undefined') {
                    profit_by_date_datatable = $('#profit_by_date_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/date",
                            "data": function ( d ) {
                                d.location_id = $('#profit_tabs_location_filter').val();
                                d.start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'transaction_date', name: 'sale.transaction_date'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_date_table'), 'gross-profit');
                            $('#profit_by_date_table .footer_total').text(total_profit);
                            __currency_convert_recursively($('#profit_by_date_table'));
                        },
                    });
                } else {
                    profit_by_date_datatable.ajax.reload();
                }
            } else if (target == '#profit_by_customer') {
                if(typeof profit_by_customers_table == 'undefined') {
                    profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                        processing: true,
                        serverSide: true,
                        "ajax": {
                            "url": "/reports/get-profit/customer",
                            "data": function ( d ) {
                                d.location_id = $('#profit_tabs_location_filter').val();
                                d.start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                            }
                        },
                        columns: [
                            { data: 'customer', name: 'CU.name'  },
                            { data: 'gross_profit', "searchable": false},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_customer_table'), 'gross-profit');
                            $('#profit_by_customer_table .footer_total').text(total_profit);
                            __currency_convert_recursively($('#profit_by_customer_table'));
                        },
                    });
                } else {
                    profit_by_customers_table.ajax.reload();
                }
            } else if (target == '#profit_by_day') {
                 var location_id = $('#profit_tabs_location_filter').val();
                var start_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .startDate.format('YYYY-MM-DD');

                var end_date = $('#profit_tabs_filter')
                                    .data('daterangepicker')
                                    .endDate.format('YYYY-MM-DD');
                var url = '/reports/get-profit/day?start_date=' + start_date + '&end_date=' + end_date + '&location_id=' + location_id;
                $.ajax({
                        url: url,
                        dataType: 'html',
                        success: function(result) {
                           $('#profit_by_day').html(result);
                            profit_by_days_table = $('#profit_by_day_table').DataTable({
                                    "searching": false,
                                    'paging': false,
                                    'ordering': false,
                            });
                            var total_profit = sum_table_col($('#profit_by_day_table'), 'gross-profit');
                           $('#profit_by_day_table .footer_total').text(total_profit);
                            __currency_convert_recursively($('#profit_by_day_table'));
                        },
                    });
            } else if (target == '#profit_by_products') {
                profit_by_products_table.ajax.reload();
            }
        });
    });
</script>


<script>
    let opening_stock = undefined;
    let opening_stock_by_sp = undefined;
    let closing_stock = undefined;
    let closing_stock_by_sp = undefined;
    let purchase_details = undefined;
    let sell_details = undefined;
    let transaction_totals = undefined;
    let gross_profit = undefined;
    let modules_data = undefined;
    let module_gross_profit = undefined;
    let sell_by_subtype = undefined;
    let left_side_module_data = undefined;
    let module_total = undefined;
    let right_side_module_data = undefined;


    function calculate_gross_profit(){
        if (gross_profit === undefined || module_gross_profit === undefined){
            return
        }

        let total_gross_profit = 0;
        total_gross_profit += parseFloat(gross_profit);
        total_gross_profit += parseFloat(module_gross_profit.gross_profit);
        update_data('gross_profit', total_gross_profit);
        if (module_gross_profit.gross_profit_label !== '') {
            update_data('gross_profit_label', module_gross_profit.gross_profit_label);
        }
    }

    function calculate_net_gross_profit(){
        calculate_gross_profit();
        let net_profit = 0;
        if (module_total === undefined || gross_profit === undefined || transaction_totals === undefined || sell_details === undefined || purchase_details === undefined) {
            return
        }
        net_profit += parseFloat(module_total);
        net_profit += parseFloat(gross_profit);
        if (transaction_totals !== undefined) {
            net_profit += parseFloat(transaction_totals['total_recovered']);
            net_profit += parseFloat(transaction_totals['total_sell_round_off']);
            net_profit += parseFloat(transaction_totals['total_reward_amount']);
            net_profit -= parseFloat(transaction_totals['total_adjustment']);
            net_profit -= parseFloat(transaction_totals['total_expense']);
            net_profit += parseFloat(transaction_totals['total_purchase_discount']);
            net_profit -= parseFloat(transaction_totals['total_transfer_shipping_charges']);
        }

        if (sell_details !== undefined) {
            net_profit += parseFloat(sell_details['total_sell_shipping_charge']);
        }

        if (purchase_details !== undefined) {
            net_profit -= parseFloat(purchase_details['total_purchase_shipping_charge']);
        }

        update_data('net_profit', net_profit)
    }


    function update_data(data_attr, value){
        // add the "\ufdfc" in value which is rial symbol\
        value = value + "\ufdfc"
        $(`[data-profit-loss="${data_attr}"]`).text(value);
    }


    function get_opening_stock(data) {
        data['data_type'] = 'opening_stock';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Opening Stock", result);
                // element with attr data-profit-loss
                // result.opening_stock to fixed
                update_data('opening_stock', parseFloat(result.opening_stock).toFixed(2))
                // $('[data-profit-loss="opening_stock"]').text(result.opening_stock);
                calculate_net_gross_profit()
            },
        });
    }

    function get_opening_stock_by_sp(data) {
        data['data_type'] = 'opening_stock_by_sp';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Opening Stock By SP", result);
                opening_stock_by_sp = result;
                update_data('opening_stock_by_sp', parseFloat(result.opening_stock_by_sp).toFixed(2))
                calculate_net_gross_profit()
            },
        });
    }

    function get_closing_stock(data) {
        data['data_type'] = 'closing_stock';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Closing Stock", result);
                closing_stock = result;
                update_data('closing_stock', parseFloat(result.closing_stock).toFixed(2))
                calculate_net_gross_profit()
            },
        });
    }

    function get_closing_stock_by_sp(data) {
        data['data_type'] = 'closing_stock_by_sp';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Closing Stock By SP", result);
                closing_stock_by_sp = result.closing_stock_by_sp;
                update_data('closing_stock_by_sp', parseFloat(result.closing_stock_by_sp).toFixed(2))
                calculate_net_gross_profit()
            },
        });
    }

    function get_purchase_details(data) {
        data['data_type'] = 'purchase_details';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Purchase Details", result);
                purchase_details = result;
                update_data('total_purchase', parseFloat(result.total_purchase).toFixed(2))
                update_data('total_purchase_shipping_charge', parseFloat(result.total_purchase_shipping_charge).toFixed(2))
                calculate_net_gross_profit()
            },
        });
    }

    function get_sell_details(data) {
        data['data_type'] = 'sell_details';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Sell Details", result);
                sell_details = result;
                update_data('total_sell_shipping_charge', parseFloat(result.total_sell_shipping_charge).toFixed(2))
                update_data('total_sell', parseFloat(result.total_sell).toFixed(2))
                calculate_net_gross_profit()
            },
        });
    }

    function get_transaction_totals(data) {
        data['data_type'] = 'transaction_totals';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Transaction Totals", result);
                transaction_totals = result;
                update_data('total_adjustment', parseFloat(result.total_adjustment).toFixed(2))
                update_data('total_expense', parseFloat(result.total_expense).toFixed(2))
                update_data('total_purchase_discount', parseFloat(result.total_purchase_discount).toFixed(2))
                update_data('total_purchase_return', parseFloat(result.total_purchase_return).toFixed(2))
                update_data('total_recovered', parseFloat(result.total_recovered).toFixed(2))
                update_data('total_reward_amount', parseFloat(result.total_reward_amount).toFixed(2))
                update_data('total_sell_discount', parseFloat(result.total_sell_discount).toFixed(2))
                update_data('total_sell_return', parseFloat(result.total_sell_return).toFixed(2))
                update_data('total_sell_round_off', parseFloat(result.total_sell_round_off).toFixed(2))
                update_data('total_transfer_shipping_charges', parseFloat(result.total_transfer_shipping_charges).toFixed(2))
                calculate_net_gross_profit()
            },
        });
    }

    function get_gross_profit(data) {
        data['data_type'] = 'gross_profit';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Gross Profit", result);
                gross_profit = parseFloat(result).toFixed(2);
                calculate_net_gross_profit()
            },
        });
    }

    function get_modules_data(data) {
        data['data_type'] = 'modules_data';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Modules Data", result);
                modules_data = result;
                left_side_module_data = result.left_side_module_data;
                let table = $("#left_side_table");
                const lsmd_add_to_net_profit = $("lsmd_add_to_net_profit")
                for (let i = 0; i < left_side_module_data.length; i++) {
                    const module_data = left_side_module_data[i];
                    const row = `<tr>
                                    <th>${module_data.label}:</th>
                                    <td>
                                        <span class="display_currency" data-currency_symbol="true">${module_data.value}</span>
                                    </td>
                                </tr>`
                    table.append(row)
                    if (module_data.add_to_net_profit) {
                        lsmd_add_to_net_profit.append(`+ ${module_data.label}`)
                    }
                }

                right_side_module_data = result.right_side_module_data;
                table = $("#right_side_table");
                const rsmd_add_to_net_profit = $("rsmd_add_to_net_profit")
                for (let i = 0; i < right_side_module_data.length; i++) {
                    const module_data = right_side_module_data[i];
                    const row = `<tr>
                                    <th>${module_data.label}:</th>
                                    <td>
                                        <span class="display_currency" data-currency_symbol="true">${module_data.value}</span>
                                    </td>
                                </tr>`
                    table.append(row)
                    if (module_data.add_to_net_profit) {
                        rsmd_add_to_net_profit.append(`+ ${module_data.label}`)
                    }
                }
                module_total = parseFloat(result.module_total).toFixed(2);
                calculate_net_gross_profit()
            },
        });
    }

    function get_modules_gross_profit_data(data) {
        data['data_type'] = 'module_gross_profit';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Modules Gross Profit", result);
                module_gross_profit = result;
                calculate_net_gross_profit()
            },
        });
    }


    function get_sell_by_subtype(data) {
        data['data_type'] = 'sell_by_subtype';
        $.ajax({
            method: 'GET',
            url: '/reports/profit-loss',
            data: data,
            async: true,
            success: function(result) {
                console.log("Sell By Subtype", result);
                sell_by_subtype = result;
                const list_sel = $("#sell_by_type_list")

                for (let i = 0; i < sell_by_subtype.length; i++) {
                    const sell = sell_by_subtype[i];
                    const li = `<li>
                                    <span class="display_currency" data-currency_symbol="true">
                                        ${sell.total_before_tax}
                                    </span>
                                    ${sell.sub_type ? `<small class="text-muted">(${sell.sub_type})</small>` : ''}
                                </li>`
                    list_sel.append(li)
                }

            },
        });
    }

    function updateProfitLoss(start = null, end = null, location_id = null, selector = null) {

        if (start == null) {
            var start = $('#profit_loss_date_filter')
                .data('daterangepicker').startDate.format('YYYY-MM-DD');
        }
        if (end == null) {
            var end = $('#profit_loss_date_filter')
                .data('daterangepicker')
                .endDate.format('YYYY-MM-DD');
        }
        if (location_id == null) {
            var location_id = $('#profit_loss_location_filter').val();
        }
        var data = { start_date: start, end_date: end, location_id: location_id };
        // selector = selector == null ? $('#pl_data_div') : selector;
        // var loader = '<div class="text-center">' + __fa_awesome() + '</div>';
        // selector.html(loader);
        get_opening_stock(data);
        get_opening_stock_by_sp(data);
        get_closing_stock(data);
        get_closing_stock_by_sp(data);
        get_purchase_details(data);
        get_sell_details(data);
        get_transaction_totals(data);
        get_gross_profit(data);
        get_modules_data(data);
        get_sell_by_subtype(data);

}

updateProfitLoss();

</script>

@endsection
