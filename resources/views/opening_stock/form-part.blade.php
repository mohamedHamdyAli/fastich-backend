<div class="row">
    <div class="col-sm-12">
        @if($stocks != '')
        @foreach($stocks as $key => $stock)
        @foreach($stock as $subkey => $var)
        @php
        $var = (object) $var;
        @endphp
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">@lang('sale.location'): {{$var->location_name}}</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-12">
                        <table
                            class="table table-condensed table-bordered table-th-green text-center table-striped add_opening_stock_table">
                            <thead>
                                <tr>
                                    <th>@lang( 'product.product_name' )</th>
                                    <th>@lang( 'lang_v1.quantity_add' )</th>
                                    <th>@lang( 'purchase.unit_cost_before_tax' )</th>
                                    <th>@lang( 'purchase.subtotal_before_tax' )</th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>

                                @php
                                $subtotal = 0;
                                $qty = $var->quantity_remaining ?? 0;
                                $purcahse_price = $var->purchase_price ?? 0;
                                $row_total = $qty * $purcahse_price;
                                $subtotal += $row_total;
                                @endphp
                                <tr>
                                    <td>
                                        {{ $var->product_name }}
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            {{ @format_quantity($qty)}}
                                            {!! Form::hidden('quantity_remaining',@format_quantity($qty)); !!}
                                            <span class="input-group-addon">
                                                {{ $var->short_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        {{@num_format($purcahse_price)}}
                                    </td>
                                    <td>
                                        <span class="row_subtotal_before_tax_old">{{@num_format($row_total)}}</span>
                                    </td>
                                    <td>

                                        <button type="button" class="btn btn-primary btn-xs add_stock_row"
                                            data-sub-key="{{$var->variation_id}}" data-row-html='<tr>
												<td>
													{{  $var->product_name }}
												</td>
												<td>
													<div class=" input-group">
													<input class="form-control input-sm input_number purchase_quantity"
														required="" name="stocks[{{$var->location_id}}][{{$var->variation_id}}][__subkey__][quantity]"
														type="text" value="0">
														<input name="stocks[{{$var->location_id}}][{{$var->variation_id}}][__subkey__][location_name]" type="hidden" value="{{$var->location_name}}">
													<span class="input-group-addon">
														{{ $var->short_name }}
													</span>
													</div>
												</td>
												<td>
													<input class="form-control input-sm input_number unit_price" required=""
														name="stocks[{{$var->location_id}}][{{$var->variation_id}}][__subkey__][purchase_price]" type="text"
														value="{{@num_format($purcahse_price)}}">
												</td>
												<td>
													<span class="row_subtotal_before_tax">
														0.00
													</span>
												</td>
												<td>&nbsp;</td>
												</tr>'>
                                            <i class="fa fa-plus"></i></button>



                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="hide">
                                <tr>
                                    <td colspan="3"></td>
                                    <td>
                                        <strong>@lang( 'lang_v1.total_amount_exc_tax' ): </strong> <span
                                            id="total_subtotal">{{@num_format($subtotal)}}</span>
                                        <input type="hidden" id="total_subtotal_hidden" value=0>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--box end-->
        @endforeach
        @endforeach
        @else
        <h3>@lang( 'lang_v1.product_not_assigned_to_any_location' )</h3>
        @endif
    </div>
</div>
