@extends('layouts.app')
@section('title', __('report.trending_products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.trending_products')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <form id="trending_products_form">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="location_id">{{ __('purchase.business_location') }}:</label>
                        <select id="business_locations_select" name="location_id" class="form-control select2" style="width:100%">
                            {{-- @foreach($business_locations as $location_id => $location)
                                <option value="{{ $location_id }}">{{ $location }}</option>
                            @endforeach --}}
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="category_id">{{ __('product.category') }}:</label>
                        <select id="categories_select" name="category" class="form-control select2" style="width:100%" id="category_id">
                            <option value="">{{ __('messages.all') }}</option>
                            {{-- @foreach($categories as $category_id => $category)
                                <option value="{{ $category_id }}">{{ $category }}</option>
                            @endforeach --}}
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="sub_category_id">{{ __('product.sub_category') }}:</label>
                        <select name="sub_category" class="form-control select2" style="width:100%" id="sub_category_id">
                            <option value="">{{ __('messages.all') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="brand">{{ __('product.brand') }}:</label>
                        <select id="brands_select" name="brand" class="form-control select2" style="width:100%">
                            <option value="">{{ __('messages.all') }}</option>
                            {{-- @foreach($brands as $brand_id => $brand)
                                <option value="{{ $brand_id }}">{{ $brand }}</option>
                            @endforeach --}}
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="unit">{{ __('product.unit') }}:</label>
                        <select id="units_select" name="unit" class="form-control select2" style="width:100%">
                            <option value="">{{ __('messages.all') }}</option>
                            {{-- @foreach($units as $unit_id => $unit)
                                <option value="{{ $unit_id }}">{{ $unit }}</option>
                            @endforeach --}}
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="trending_product_date_range">{{ __('report.date_range') }}:</label>
                        <input type="text" name="date_range" placeholder="{{ __('lang_v1.select_a_date_range') }}" class="form-control" id="trending_product_date_range" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="limit">{{ __('lang_v1.no_of_products') }}:</label>
                        <input type="number" name="limit" placeholder="{{ __('lang_v1.no_of_products') }}" class="form-control" min="1" value="5">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="product_type">{{ __('product.product_type') }}:</label>
                        <select name="product_type" class="form-control select2" style="width:100%">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="single">{{ __('lang_v1.single') }}</option>
                            <option value="variable">{{ __('lang_v1.variable') }}</option>
                            <option value="combo">{{ __('lang_v1.combo') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12">
                    <button id="trending_report_submit" type="submit" class="btn btn-primary pull-right">{{ __('report.apply_filters') }}</button>
                </div> 
            </form>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title')
                    @lang('report.top_trending_products') @show_tooltip(__('tooltip.top_trending_products'))
                @endslot
                <div id="trending_products_chart">

                </div>
            @endcomponent
        </div>
    </div>
    <div class="row no-print">
        <div class="col-sm-12">
            <button type="button" class="btn btn-primary pull-right" 
            aria-label="Print" onclick="window.print();"
            ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script>
        let categories = [];
        let brands = [];
        let units = [];
        let business_locations = [];
        
        function get_categories() {
            $.ajax({
                method: 'GET',
                url: '/reports/trending-products-categories',
                async: true,
                success: function(result) {
                    console.log("Fetched Categories", result);
                    categories = result;
                    for(const key in categories) {
                        $('#categories_select').append('<option value="' + key + '">' + categories[key] + '</option>');
                    }
                },
            });
        }

        function get_brands() {
            $.ajax({
                method: 'GET',
                url: '/reports/trending-products-brands',
                async: true,
                success: function(result) {
                    console.log("Fetched Brands", result);
                    brands = result;
                    for (const key in brands) {
                        $('#brands_select').append('<option value="' + key + '">' + brands[key] + '</option>');
                    }
                },
            });
        }

        function get_units() {
            $.ajax({
                method: 'GET',
                url: '/reports/trending-products-units',
                async: true,
                success: function(result) {
                    console.log("Fetched Units", result);
                    units = result;
                    for (const key in units) {
                        $('#units_select').append('<option value="' + key + '">' + units[key] + '</option>');
                    }
                },
            });
        }

        function get_business_locations() {
            $.ajax({
                method: 'GET',
                url: '/reports/trending-products-locations',
                async: true,
                success: function(result) {
                    console.log("Fetched Business Locations", result);
                    business_locations = result;
                    for (const key in business_locations) {
                        if (key !== ""){
                        $('#business_locations_select').append('<option value="' + key + '">' + business_locations[key] + '</option>');

                        } else {
                            $('#business_locations_select').append('<option selected value="' + key + '">' + business_locations[key] + '</option>');
                        }
                    }
                },
            });
        }

        function getTrendingProductsData(data = null){
            const loader = '<i class="fas fa-sync fa-spin fa-fw margin-bottom"></i>';
            $("#trending_products_chart").html(loader);
            $.ajax({
                method: 'GET',
                url: `/reports/trending-products-data?${data}`,
                async: true,
                data: data,
                success: function(result) {
                    console.log("Fetched Trending Products Data", result);
                    const labels = result['labels'];
                    const dataset = [result['dataset']];
                    $("#trending_products_chart").html('');
                    $("#trending_report_submit").prop('disabled', false);
                    Highcharts.chart('trending_products_chart', {
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
                        // legend: {
                        //     align: 'right',
                        // },
                        // yAxis: {
                        //     title: {
                        //         text: title
                        //     }
                        // },
                        // tooltip: {
                        //     useHTML: true,
                        //     formatter: function () {
                        //         console.log(this)
                        //         return '<span style="white-space: nowrap;">' + this.x + '<br/>' +
                        //                '<span style="color:' + this.color + '">' + this.series.name + '</span>: ' + this.y + '</span>';
                        //     },
                        //     shared: false
                        // }, 
                        series: dataset
                    });

                },
            });
        }


        $(document).ready(function() {

            // add submit listner on trending_products_form
            $('#trending_products_form').submit(function(e) {
                e.preventDefault();
                // store the form data and show in the console
                const formData = $('#trending_products_form').serializeArray();
                const formDataObject = {};

                formData.forEach(function(field) {
                    formDataObject[field.name] = field.value;
                });

                getTrendingProductsData(formDataObject)
                
            });

            get_categories();
            get_brands();
            get_units();
            get_business_locations();
            let fd = $('#trending_products_form').serializeArray();
            const fdo = {};
            fd.forEach(function(field) {
                    fdo[field.name] = field.value;
            });
            getTrendingProductsData(fd);
        });


    </script>
@endsection