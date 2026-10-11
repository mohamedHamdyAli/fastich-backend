@extends('layouts.restaurant')
@section('title', __( 'restaurant.kitchen' ))

@section('content')
<!-- Main content -->
<section class="content min-height-90hv no-print">

    <div class="row">
        <div class="col-md-12 text-center">
            <h3>@lang( 'restaurant.all_orders' ) - @lang( 'restaurant.kitchen' )
                @show_tooltip(__('lang_v1.tooltip_kitchen'))</h3>
        </div>
    </div>


    <div class="box">
        <div class="box-header" style="display: flex;">
            <div>
                <label style="margin-right: 10px;font-size: 20px">Sorting</label>
                <button id="desc" value="desc" style="padding: 4px 10px"><i
                        class="fas fa-angle-down fa-2x"></i></button>
                <button id="asc" value="asc" style="padding: 4px 10px;"><i class="fa fa-angle-up fa-2x"></i></button>
            </div>
            <div style="margin-left: auto;">
                {{--                <select name="kitchen_station" class="form-control select2" id="kitchen_station">--}}
                {{--                    <option value="">Select Kitchen Station</option>--}}
                {{--                    @foreach($kitchenStations as $key => $kitchenStation)--}}
                {{--                    <option value="{{$key}}">{{$kitchenStation}}</option>--}}
                {{--                    @endforeach--}}
                {{--                </select>--}}
                <select name="refresh_time" class="form-control select2" id="refresh_time">
                    <option value="">Manual Refresh</option>
                    <option value="1">1 Min Refresh</option>
                    <option value="2">2 Min Refresh</option>
                </select>
                <button type="button" class="btn btn-sm btn-primary pull-right" id="refresh_orders"
                    style="margin-left: 20px"><i class="fas fa-sync"></i> @lang( 'restaurant.refresh' )</button>
            </div>
        </div>
        <div class="box-body">
            <input type="hidden" id="orders_for" value="kitchen">
            <div class="row" id="orders_div">
                @include('restaurant.partials.show_orders', array('orders_for' => 'kitchen'))
            </div>
        </div>
        <div class="overlay hide">
            <i class="fas fa-sync fa-spin"></i>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    $(document).on('click', 'a.mark_as_cooked_btn', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: "info",
            buttons: true,
        }).then((willDelete) => {
            if (willDelete) {
                var _this = $(this);
                var href = _this.data('href');
                $.ajax({
                    method: "GET",
                    url: href,
                    dataType: "json",
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            _this.closest('.order_div').remove();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });
});

$(document).ready(function() {
    $(document).on('click', 'a.mark_as_opened_btn', function(e) {
        e.preventDefault();
        var _this = $(this);
        _this.closest('.order_div').find('table').removeClass("bg-info");

    });
});
</script>
@endsection
