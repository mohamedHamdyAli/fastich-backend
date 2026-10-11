@extends('layouts.app')
@section('title', __('home.home'))

@section('content')




<!-- Content Header (Page header) -->
<section class="content-header content-header-custom">
    <h1>{{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}
    </h1>
    <input id="termsConditionNotDone" type="hidden" value={{$termsConditionNotDoneStatus}} />
</section>
<!-- Main content -->
<section class="content content-custom no-print">
    <br>
    @if (auth()->user()->can('dashboard.data'))
    <div class="row">

        <div class="col-md-4 col-xs-12">
            @if (count($all_locations) > 1)
            {!! Form::select('dashboard_location', $all_locations, null, [
            'class' => 'form-control select2',
            'placeholder' => __('lang_v1.select_location'),
            'id' => 'dashboard_location',
            ]) !!}
            @endif
        </div>
        <div class="col-md-8 col-xs-12">
            <div class="btn-group pull-right" data-toggle="buttons">
                <label class="btn btn-info active">
                    <input checked data-end="{{ $date_filters['yesterday'] }}"
                        data-start="{{ $date_filters['yesterday'] }}" name="date-filter"
                        type="radio">{{ __('home.yesterday') }}
                </label>
                <label class="btn btn-info active">
                    <input checked data-end="{{ date('Y-m-d') }}" data-start="{{ date('Y-m-d') }}" name="date-filter"
                        type="radio"> {{ __('home.today') }}
                </label>
                <label class="btn btn-info">
                    <input data-end="{{ $date_filters['this_week']['end'] }}"
                        data-start="{{ $date_filters['this_week']['start'] }}" name="date-filter" type="radio">
                    {{ __('home.this_week') }}
                </label>
                <label class="btn btn-info">
                    <input data-end="{{ $date_filters['this_month']['end'] }}"
                        data-start="{{ $date_filters['this_month']['start'] }}" name="date-filter" type="radio">
                    {{ __('home.this_month') }}
                </label>
                <label class="btn btn-info">
                    <input data-end="{{ $date_filters['this_fy']['end'] }}"
                        data-start="{{ $date_filters['this_fy']['start'] }}" name="date-filter" type="radio">
                    {{ __('home.this_fy') }}
                </label>
            </div>
        </div>
    </div>
    <br>
    <div class="row row-custom">
        <!-- Total Sales -->
        <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('home.total_sell') }}</span>
                    <span class="info-box-number total_sell"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
            </div>
        </div>
        <!-- /.col -->

        <!-- Total Paid Payment -->
        <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('home.total_net_payment') }}</span>
                    <span class="info-box-number total_paid_payment"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
            </div>
        </div>
        <!-- /.col -->

        <!-- Total Reurn Payment -->
        <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('home.total_returned') }}</span>
                    <span class="info-box-number total_return_payment"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
            </div>
        </div>
        <!-- /.col -->

        <div class="col-md-3 col-sm-6 col-xs-12 col-custom hide">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-aqua"><i class="ion ion-cash"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">{{ __('home.total_purchase') }}</span>
                    <span class="info-box-number total_purchase"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <!-- /.col -->
        <div class="col-md-3 col-sm-6 col-xs-12 col-custom hide">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-yellow">
                    <i class="fa fa-dollar"></i>
                    <i class="fa fa-exclamation"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">{{ __('home.purchase_due') }}</span>
                    <span class="info-box-number purchase_due"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>



        <!-- fix for small devices only -->
        <!-- <div class="clearfix visible-sm-block"></div> -->
        <div class="col-md-3 col-sm-6 col-xs-12 col-custom hide">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-yellow">
                    <i class="ion ion-ios-paper-outline"></i>
                    <i class="fa fa-exclamation"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">{{ __('home.invoice_due') }}</span>
                    <span class="info-box-number invoice_due"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <!-- /.col -->
    </div>
    <div class="row row-custom hide">
        <!-- expense -->
        <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
            <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-red">
                    <i class="fas fa-minus-circle"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        {{ __('lang_v1.expense') }}
                    </span>
                    <span class="info-box-number total_expense"><i
                            class="fas fa-sync fa-spin fa-fw margin-bottom"></i></span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
    </div>
    <div id="after_sale_purchase_totals">

    </div>
    {{-- @if (!empty($widgets['after_sale_purchase_totals']))
                @foreach ($widgets['after_sale_purchase_totals'] as $widget)
                    {!! $widget !!}
                @endforeach
            @endif --}}
    @if (!empty($all_locations))
    <!-- sales chart start -->
    <div class="row">
        <div class="col-sm-12">

            @component('components.widget', ['class' => 'box-primary', 'title' => __('home.sells_last_07_days')])
            <div id="sell_chart_one"></div>
            @endcomponent
        </div>
    </div>
    @endif
    <div id="after_sales_last_30_days">

    </div>
    {{-- @if (!empty($widgets['after_sales_last_30_days']))
                @foreach ($widgets['after_sales_last_30_days'] as $widget)
                    {!! $widget !!}
                @endforeach
            @endif --}}
    @if (!empty($all_locations))
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('home.sells_current_fy')])
            <div id="chart_for_this_fy">

            </div>
            @endcomponent
        </div>
    </div>
    @endif
    <!-- sales chart end -->

    <div id="after_sales_current_fy">

    </div>
    {{-- @if (!empty($widgets['after_sales_current_fy']))
                @foreach ($widgets['after_sales_current_fy'] as $widget)
                    {!! $widget !!}
                @endforeach
            @endif --}}
    <!-- products less than alert quntity -->

    <div class="row">

        <div class="col-sm-6">
            @component('components.widget', ['class' => 'box-warning'])
            @slot('icon')
            <i aria-hidden="true" class="fa fa-exclamation-triangle text-yellow"></i>
            @endslot
            @slot('title')
            {{ __('lang_v1.sales_payment_dues') }} @show_tooltip(__('lang_v1.tooltip_sales_payment_dues'))
            @endslot
            <table class="table table-bordered table-striped" id="sales_payment_dues_table">
                <thead>
                    <tr>
                        <th>@lang('contact.customer')</th>
                        <th>@lang('sale.invoice_no')</th>
                        <th>@lang('home.due_amount')</th>
                    </tr>
                </thead>
            </table>
            @endcomponent
        </div>

        <div class="col-sm-6">
            @component('components.widget', ['class' => 'box-warning'])
            @slot('icon')
            <i aria-hidden="true" class="fa fa-exclamation-triangle text-yellow"></i>
            @endslot
            @slot('title')
            {{ __('lang_v1.purchase_payment_dues') }} @show_tooltip(__('tooltip.payment_dues'))
            @endslot
            <table class="table table-bordered table-striped" id="purchase_payment_dues_table">
                <thead>
                    <tr>
                        <th>@lang('purchase.supplier')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('home.due_amount')</th>
                    </tr>
                </thead>
            </table>
            @endcomponent
        </div>
    </div>


    <div class="row">
        <div class="@if (session('business.enable_product_expiry') != 1 &&
                        auth()->user()->can('stock_report.view')) col-sm-12 @else col-sm-6 @endif">
            @component('components.widget', ['class' => 'box-warning'])
            @slot('icon')
            <i aria-hidden="true" class="fa fa-exclamation-triangle text-yellow"></i>
            @endslot
            @slot('title')
            {{ __('home.product_stock_alert') }} @show_tooltip(__('tooltip.product_stock_alert'))
            @endslot
            <table class="table table-bordered table-striped" id="stock_alert_table">
                <thead>
                    <tr>
                        <th>@lang('sale.product')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('report.current_stock')</th>
                    </tr>
                </thead>
            </table>
            @endcomponent
        </div>
        @can('stock_report.view')
        @if (session('business.enable_product_expiry') == 1)
        <div class="col-sm-6">
            @component('components.widget', ['class' => 'box-warning'])
            @slot('icon')
            <i aria-hidden="true" class="fa fa-exclamation-triangle text-yellow"></i>
            @endslot
            @slot('title')
            {{ __('home.stock_expiry_alert') }} @show_tooltip( __('tooltip.stock_expiry_alert', [ 'days'
            =>session('business.stock_expiry_alert_days', 30) ]) )
            @endslot
            <input id="stock_expiry_alert_days" type="hidden"
                value="{{ \Carbon::now()->addDays(session('business.stock_expiry_alert_days', 30))->format('Y-m-d') }}">
            <table class="table table-bordered table-striped" id="stock_expiry_alert_table">
                <thead>
                    <tr>
                        <th>@lang('business.product')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('report.stock_left')</th>
                        <th>@lang('product.expires_in')</th>
                    </tr>
                </thead>
            </table>
            @endcomponent
        </div>
        @endif
        @endcan
    </div>
    <div id="after_dashboard_reports">

    </div>
    {{-- @if (!empty($widgets['after_dashboard_reports']))
                @foreach ($widgets['after_dashboard_reports'] as $widget)
                    {!! $widget !!}
                @endforeach
            @endif --}}
    @endif
</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
{{-- <script src="https://code.highcharts.com/highcharts.js"></script> --}}
<script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
<script>
function getHomeWidgets() {
    $.ajax({
        url: "{{ route('home_widgets') }}",
        method: 'GET',
        success: function(response) {
            // Handle the response data as needed
            console.log(response);
            const widgts = response['widgets'];
            if (widgts['after_sale_purchase_totals']) {
                $("#after_sale_purchase_totals").html(widgts['after_sale_purchase_totals']);
            }
            if (widgts['after_sales_last_30_days']) {
                $("#after_sales_last_30_days").html(widgts['after_sales_last_30_days']);
            }
            if (widgts['after_sales_current_fy']) {
                $("#after_sales_current_fy").html(widgts['after_sales_current_fy']);
            }
            if (widgts['after_dashboard_reports']) {
                $("#after_dashboard_reports").html(widgts['after_dashboard_reports']);
            }

        },
        error: function(error) {
            // Handle errors if any
            console.error(error);
        }
    });
}
let can_dashboard_data;

const financialYear = {!! isset($financialYear) ? json_encode($financialYear) : 'undefined' !!};
const currency = {!! isset($currency) ? json_encode($currency) : 'undefined' !!};
const allLocations = {!! isset($all_locations) ? json_encode($all_locations) : 'undefined' !!};

// console.log(allLocations, financialYear, currency);


// Fetch Chart for Current Financial Year
function getChartForThisFY(financialYear, allLocations, currency) {
    const loader = '<i class="fas fa-sync fa-spin fa-fw margin-bottom"></i>';
    $("#chart_for_this_fy").html(loader);
    $.ajax({
        url: "{{ route('chart_for_this_fy') }}",
        method: 'POST',
        data: {
            financialYear: financialYear,
            all_locations: allLocations,
            currency: currency,
            "_token": "{{ csrf_token() }}"
        },
        success: function(response) {
            console.log(response);
            const dataset = response["dataset"];
            const title = response['title'];
            const labels = response['labels'];

            $("#chart_for_this_fy").html('');
            Highcharts.chart('chart_for_this_fy', {
                title: {
                    text: null
                },
                xAxis: {
                    categories: labels,
                    title: {
                        text: ""
                    }
                },
                credits: {
                    enabled: false
                },
                legend: {
                    align: 'right',
                },
                yAxis: {
                    title: {
                        text: title
                    }
                },
                tooltip: {
                    useHTML: true,
                    formatter: function() {
                        console.log(this)
                        return '<span style="white-space: nowrap;">' + this.x + '<br/>' +
                            '<span style="color:' + this.color + '">' + this.series.name +
                            '</span>: ' + this.y + '</span>';
                    },
                    shared: false
                },
                series: dataset
            });
        },
        error: function(error) {
            // Handle errors if any
            console.error(error);
        }
    });
}

function getSellChartOne(currency, allLocations) {
    const loader = '<i class="fas fa-sync fa-spin fa-fw margin-bottom"></i>';
    $("#sell_chart_one").html(loader);
    $.ajax({
        url: "{{ route('sell_chart_one') }}",
        method: 'POST',
        data: {
            currency: currency,
            all_locations: allLocations,
            "_token": "{{ csrf_token() }}"
        },
        success: function(response) {
            console.log(response);
            const dataset = response["dataset"];
            const title = response['title'];
            const labels = response['labels'];

            $("#sell_chart_one").html('');
            Highcharts.chart('sell_chart_one', {
                title: {
                    text: null
                },
                xAxis: {
                    categories: labels,
                    title: {
                        text: ""
                    }
                },
                credits: {
                    enabled: false
                },
                legend: {
                    align: 'right',
                },
                yAxis: {
                    title: {
                        text: title
                    }
                },
                tooltip: {
                    useHTML: true,
                    formatter: function() {
                        console.log(this)
                        return '<span style="white-space: nowrap;">' + this.x + '<br/>' +
                            '<span style="color:' + this.color + '">' + this.series.name +
                            '</span>: ' + this.y + '</span>';
                    },
                    shared: false
                },
                series: dataset
            });
        },
        error: function(error) {
            // Handle errors if any
            console.error(error);
        }
    });
}
if (
    typeof financialYear !== 'undefined' &&
    typeof currency !== 'undefined' &&
    typeof allLocations !== 'undefined'
) {
    const defaultStart = $('input[name="date-filter"]:checked').data('start');
    const defaultEnd = $('input[name="date-filter"]:checked').data('end');
    if (typeof update_statistics === 'function') {
        update_statistics(defaultStart, defaultEnd);
    }

    getHomeWidgets();
    getSellChartOne(currency, allLocations);
    getChartForThisFY(financialYear, allLocations, currency);
}
</script>
@if (!empty($all_locations))
{{-- {!! $sells_chart_1->script() !!} --}}
{{-- {!! $sells_chart_2->script() !!} --}}
@endif
@endsection
