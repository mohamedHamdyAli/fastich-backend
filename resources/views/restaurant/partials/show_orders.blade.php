@forelse($orders as $order)
<div class="col-md-3 col-xs-6 order_div">
    <div class="small-box bg-gray">
        <div class="inner">
            <h4 class="text-center"><strong>@lang('restaurant.order_no') {{$order->call_no}}</strong></h4>
            @php
            if($order->order_opnened){
            $bg_opened_color = '';
            } else{
            $bg_opened_color = 'bg-info';
            }
            if($order->payment_status == 'due') {
            $bg_payment_color = 'bg-maroon';
            } else if($order->payment_status == 'paid') {
            $bg_payment_color = 'bg-green';
            } else{
            $bg_payment_color = '';

            }
            @endphp
            <table class="table no-margin no-border table-slim {{$bg_opened_color}}">

                <tr>
                    <th>@lang('sale.payments')</th>
                    <td>{{$order->payment_status}}</td>
                </tr>

                <tr>
                    <th>@lang('sale.invoice_no')</th>
                    <td>{{$order->invoice_no}}</td>
                </tr>
                @php
                if($order->created_at == $order->updated_at) {
                $bg_date_color = '';
                } else if($order->created_at != $order->updated_at) {
                $bg_date_color = 'bg-teal';
                } else{
                $bg_date_color = '';

                }
                @endphp
                <tr>
                    <th>@lang('restaurant.placed_at')</th>
                    <td>{{@format_date($order->created_at)}} {{ @format_time($order->created_at)}}</td>
                </tr>
                <tr class="">
                    <th>@lang('restaurant.updated_at')</th>
                    <td>{{@format_date($order->updated_at)}} {{ @format_time($order->updated_at)}}</td>
                </tr>
                <tr class="hide">
                    <th>@lang('restaurant.order_status')</th>
                    @php
                    $count_sell_line = count($order->sell_lines);
                    $count_cooked = count($order->sell_lines->where('res_line_order_status', 'cooked'));
                    $count_served = count($order->sell_lines->where('res_line_order_status', 'served'));
                    $order_status = 'received';
                    if($count_cooked == $count_sell_line) {
                    $order_status = 'cooked';
                    } else if($count_served == $count_sell_line) {
                    $order_status = 'served';
                    } else if ($count_served > 0 && $count_served < $count_sell_line) { $order_status='partial_served' ;
                        } else if ($count_cooked> 0 && $count_cooked < $count_sell_line) {
                            $order_status='partial_cooked' ; } @endphp <td><span
                                class="label @if($order_status == 'cooked' ) bg-red @elseif($order_status == 'served') bg-green @elseif($order_status == 'partial_cooked') bg-orange @else bg-light-blue @endif">@lang('restaurant.order_statuses.'
                                . $order_status) </span></td>
                </tr>
                <tr>
                    <th>@lang('contact.customer')</th>
                    <td>{{$order->customer_name}}</td>
                </tr>
                <tr>
                    <th>@lang('restaurant.table')</th>
                    <td>{{$order->table_name}}</td>
                </tr>
                <tr>
                    <th>@lang('restaurant.service_staff')</th>
                    <td>{{$order->waiter_first_name}} {{$order->waiter_last_name}}</td>
                </tr>
                <tr>
                    <th>@lang('sale.location')</th>
                    <td>{{$order->business_location}}</td>
                </tr>
                <!-- <tr>
                    <th>Order Items</th>
                    <td dir="rtl">
                        @foreach($order->sell_lines as $saleLine)
                        <span>{{$saleLine->quantity}} x
                            {{$saleLine->product->name}}{{$loop->last ? '' :','}}</span>
                        @endforeach

                    </td>
                </tr> -->
            </table>
        </div>
        <a href="#" class="btn btn-flat small-box-footer mark_as_opened_btn btn-lg bg-info btn-modal"
            data-href="{{ action('SellController@show', [$order->id])}}"
            data-container=".view_modal">@lang('restaurant.order_details') <i class="fa fa-arrow-circle-right"></i></a>

        @if($orders_for == 'kitchen')
        <a href="#" class="btn btn-flat small-box-footer bg-yellow mark_as_cooked_btn btn-lg mt-5"
            data-href="{{action('Restaurant\KitchenController@markAsCooked', [$order->id])}}"><i
                class="fa fa-check-square-o"></i> @lang('restaurant.mark_as_cooked')</a>
        @elseif($orders_for == 'waiter' && $order->res_order_status != 'served')
        <a href="#" class="btn btn-flat small-box-footer bg-yellow mark_as_served_btn btn-lg mt-5"
            data-href="{{action('Restaurant\OrderController@markAsServed', [$order->id])}}"><i
                class="fa fa-check-square-o"></i> @lang('restaurant.mark_as_served')</a>
        @else
        <div class="small-box-footer bg-gray">&nbsp;</div>
        @endif

    </div>
</div>
@if($loop->iteration % 4 == 0)
<div class="hidden-xs">
    <div class="clearfix"></div>
</div>
@endif
@if($loop->iteration % 2 == 0)
<div class="visible-xs">
    <div class="clearfix"></div>
</div>
@endif
@empty
<div class="col-md-12">
    <h4 class="text-center">@lang('restaurant.no_orders_found')</h4>
</div>
@endforelse
