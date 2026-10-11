<!-- business information here -->

<div class="row">
    <!-- Logo & Header -->
    <div class="col-xs-offset-1 col-xs-10 col-xs-offset-1" style="border: 1px solid black;">
        <div class="col-xs-5 text-justify" style="margin-top: 5px;">
            <p>
                @if(!empty($receipt_details->business_name))
                <strong>
                    {{$receipt_details->business_name}}
                </strong>
                @endif

                @if(!empty($receipt_details->location_name))
                <strong>
                    <br />{{$receipt_details->location_name}}
                </strong>
                @endif
                @if(!empty($receipt_details->location_city))
                <strong>
                    <br />{{$receipt_details->location_city}}
                </strong>
                @endif
                @if(!empty($receipt_details->business_tax_number_1))
                <strong style="font-size: small;">
                    <br /><b> {{ $receipt_details->business_tax_label_1}}:</b>
                    {{$receipt_details->business_tax_number_1}}
                </strong>
                @endif
                @if(!empty($receipt_details->business_tax_number_2))
                <strong style="font-size: small;">
                    <br /><b>{{ $receipt_details->business_tax_label_2}}:</b>
                    {{$receipt_details->business_tax_number_2}}
                </strong>
                @endif
            </p>
        </div>
        <div class="col-xs-3 text-center" style="margin-top: 20px;">
            <p>
                @if(!empty($receipt_details->logo))
                <img style="width: 100px;height: 100px" alt="logo" src="{{$receipt_details->logo}}"
                    class="img img-responsive center-block">
                @endif
            </p>
        </div>
        <div class="col-xs-4 text-justify">
            @if(!empty($receipt_details->invoice_heading))
            <h4 style="font-weight: 900;">
                {!! $receipt_details->invoice_heading !!}
            </h4>
            @endif
            <p style="float: right;">
                @if(!empty($receipt_details->qrcode))
                <img width="100px" class="center-block" src="{{$receipt_details->qrcode}}" alt="QR Code" />
                @endif
            </p>
        </div>

    </div>


    <!-- Header text -->
    @if(!empty($receipt_details->header_text))
    <div class="col-xs-12" style="margin-top: 10px;">
        {!! $receipt_details->header_text !!}
        <p style="text-align: center;">
            <strong>
                @if(!empty($receipt_details->sub_heading_line1))
                {{ $receipt_details->sub_heading_line1 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line2))
                <br>{{ $receipt_details->sub_heading_line2 }}
                @endif
            </strong>
            <strong>
                @if(!empty($receipt_details->sub_heading_line3))
                <br>{{ $receipt_details->sub_heading_line3 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line4))
                <br>{{ $receipt_details->sub_heading_line4 }}
                @endif
                @if(!empty($receipt_details->sub_heading_line5))
                <br>{{ $receipt_details->sub_heading_line5 }}
                @endif
            </strong>
        </p>
    </div>
    @endif

    <!-- business information here -->
    <div class="col-xs-12" style="margin-top: 10px;">
        <div class="col-xs-8 text-left">
            <!-- Address -->
            <p>
                <!-- customer info -->
                @if(!empty($receipt_details->customer_name))
                <b>{{ $receipt_details->customer_label }}:</b>
                {{ $receipt_details->customer_name }}
                @endif
                @if(!empty($receipt_details->customer_city))
                | {!! $receipt_details->customer_city !!}
                @endif
                <!--  @if(!empty($receipt_details->customer_mobile))
                |{!! $receipt_details->customer_mobile !!}
                @endif -->
                @if(!empty($receipt_details->supplier_business_name))
                <br /><b> @lang('business.business_name' ):</b> {{ $receipt_details->supplier_business_name }}
                @endif
                @if(!empty($receipt_details->customer_tax_label))
                <br />
                <b>{{ $receipt_details->customer_tax_label }}:</b> {{ $receipt_details->customer_tax_number }}
                @endif

                @if(!empty($receipt_details->client_id_label))
                <br />
                <b>{{ $receipt_details->client_id_label }}</b> {{ $receipt_details->client_id }}
                @endif

                @if(!empty($receipt_details->customer_custom_fields))
                <br />{!! $receipt_details->customer_custom_fields !!}
                @endif
                @if(!empty($receipt_details->sales_person_label))
                <br />
                <b>{{ $receipt_details->sales_person_label }}</b> {{ $receipt_details->sales_person }}
                @endif
                @if(!empty($receipt_details->customer_rp_label))
                <br />
                <strong>{{ $receipt_details->customer_rp_label }}</strong> {{ $receipt_details->customer_total_rp }}
                @endif
            </p>
            <p>

                @if(!empty($receipt_details->contact))
                <br />{{ $receipt_details->contact }}
                @endif
                @if(!empty($receipt_details->contact) && !empty($receipt_details->website))
                ,
                @endif
                @if(!empty($receipt_details->website))
                {{ $receipt_details->website }}
                @endif
                @if(!empty($receipt_details->location_custom_fields))
                <br>{{ $receipt_details->location_custom_fields }}
                @endif
            </p>
            <p>
                @if(!empty($receipt_details->tax_info1))
                <b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
                @endif

                @if(!empty($receipt_details->tax_info2))
                <b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
                @endif
            </p>
        </div>

        <div class="col-xs-4 text-right">
            <p style=" font-size: 20px;">
                @if(!empty($receipt_details->invoice_no_prefix))
                <b>{!! $receipt_details->invoice_no_prefix !!}:
                    {!! $receipt_details->invoice_no !!}
                </b>
                @endif
            </p>
            <b>{{$receipt_details->date_label}} {{$receipt_details->invoice_date}} </b>

            @if(!empty($receipt_details->due_date_label))
            <br><b>{{$receipt_details->due_date_label}}</b> {{$receipt_details->due_date ?? ''}}
            @endif
        </div>




    </div>
</div>
<div class="row hide">
    <div class="col-xs-12">
        <p style="width: 100% !important hide" class="word-wrap">
            <span class="pull-left text-left word-wrap ">
                @if(!empty($receipt_details->types_of_service))
                <br />
                <span class="pull-left text-left">
                    <strong>{!! $receipt_details->types_of_service_label !!}:</strong>
                    {{$receipt_details->types_of_service}}

                    @if(!empty($receipt_details->types_of_service_custom_fields))
                    @foreach($receipt_details->types_of_service_custom_fields as $key => $value)
                    <br><strong>{{$key}}: </strong> {{$value}}
                    @endforeach
                    @endif
                </span>
                @endif

                <!-- Table information-->
                @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
                <br />
                <span class="pull-left text-left">
                    @if(!empty($receipt_details->table_label))
                    <b>{!! $receipt_details->table_label !!}</b>
                    @endif
                    {{$receipt_details->table}}
                </span>
                @endif
            </span>
            <span class="pull-right text-left">
                @if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
                <br>
                @if(!empty($receipt_details->brand_label))
                <b>{!! $receipt_details->brand_label !!}</b>
                @endif
                {{$receipt_details->repair_brand}}
                @endif


                @if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
                <br>
                @if(!empty($receipt_details->device_label))
                <b>{!! $receipt_details->device_label !!}</b>
                @endif
                {{$receipt_details->repair_device}}
                @endif

                @if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
                <br>
                @if(!empty($receipt_details->model_no_label))
                <b>{!! $receipt_details->model_no_label !!}</b>
                @endif
                {{$receipt_details->repair_model_no}}
                @endif

                @if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
                <br>
                @if(!empty($receipt_details->serial_no_label))
                <b>{!! $receipt_details->serial_no_label !!}</b>
                @endif
                {{$receipt_details->repair_serial_no}}<br>
                @endif
                @if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
                @if(!empty($receipt_details->repair_status_label))
                <b>{!! $receipt_details->repair_status_label !!}</b>
                @endif
                {{$receipt_details->repair_status}}<br>
                @endif

                @if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
                @if(!empty($receipt_details->repair_warranty_label))
                <b>{!! $receipt_details->repair_warranty_label !!}</b>
                @endif
                {{$receipt_details->repair_warranty}}
                <br>
                @endif

                <!-- Waiter info -->
                @if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
                <br />
                @if(!empty($receipt_details->service_staff_label))
                <b>{!! $receipt_details->service_staff_label !!}</b>
                @endif
                {{$receipt_details->service_staff}}
                @endif
            </span>
        </p>
    </div>
</div>

<div class="row hide">
    @includeIf('sale_pos.receipts.partial.common_repair_invoice')
</div>

<div class="row">
    <div class="col-xs-12">
        <br />
        <table class="table table-responsive table-slim table-bordered-print">
            <thead>
                <tr>
                    <th class="text-right" width="40%">{{$receipt_details->table_product_label}}</th>
                    <th class="text-right" width="20%">{{$receipt_details->table_qty_label}}</th>
                    <th class="text-right" width="20%">{{$receipt_details->table_unit_price_label}}</th>
                    <th class="text-right" width="20%">{{$receipt_details->table_subtotal_label}}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipt_details->lines as $line)
                <tr>
                    <td class="text-right" style="word-break: break-all;">
                        @if(!empty($line['image']))
                        <img src="{{$line['image']}}" alt="Image" width="50" style="float: left; margin-right: 8px;">
                        @endif
                        {{$line['name']}}
                        {{$line['product_variation']}} {{$line['variation']}}
                        @if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif
                        @if(!empty($line['brand'])),
                        {{$line['brand']}} @endif
                        @if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
                        @if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
                        @if(!empty($line['sell_line_note']))
                        <br>
                        <small>
                            {{$line['sell_line_note']}}
                        </small>
                        @endif
                        @if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}: {{$line['lot_number']}}
                        @endif
                        @if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:
                        {{$line['product_expiry']}} @endif

                        @if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}} </small>@endif
                        @if(!empty($line['warranty_exp_date'])) <small>- {{@format_date($line['warranty_exp_date'])}}
                        </small>@endif
                        @if(!empty($line['warranty_description'])) <small>
                            {{$line['warranty_description'] ?? ''}}</small>@endif
                    </td>
                    <td class="text-right">{{$line['quantity']}} {{$line['units']}} </td>
                    <td class="text-right">{{$line['unit_price']}}</td>
                    <td class="text-right">{{$line['line_total']}}</td>
                </tr>
                @if(!empty($line['modifiers']))
                @foreach($line['modifiers'] as $modifier)
                <tr>
                    <td>
                        {{$modifier['name']}} {{$modifier['variation']}}
                        @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif
                        @if(!empty($modifier['cat_code'])), {{$modifier['cat_code']}}@endif
                        @if(!empty($modifier['sell_line_note']))({{$modifier['sell_line_note']}}) @endif
                    </td>
                    <td class="text-right">{{$modifier['quantity']}} {{$modifier['units']}} </td>
                    <td class="text-right">{{$modifier['unit_price_inc_tax']}}</td>
                    <td class="text-right">{{$modifier['line_total']}}</td>
                </tr>
                @endforeach
                @endif
                @empty
                <tr>
                    <td colspan="4">&nbsp;</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <hr />
    <div class="col-xs-6">
        <table class="table table-slim table-bordered-print">
            @if(!empty($receipt_details->payments))
            @foreach($receipt_details->payments as $payment)
            <tr>
                <th class="text-justify">{{$payment['method']}}:</th>
                <th class="text-right">{{$payment['amount']}}</th>
                <!--  <td>{{$payment['date']}}</td> -->
            </tr>
            @if(!empty($payment['payment_note']))
            <tr>
                <th class="text-justify">@lang( 'lang_v1.payment_note' ):</th>
                <th class="text-right">{{$payment['payment_note']}}</th>
            </tr>
            @endif
            @endforeach
            @endif
            <!-- Total Paid-->
            @if(!empty($receipt_details->total_paid))
            <tr>
                <th class="text-justify">
                    {!! $receipt_details->total_paid_label !!}:
                </th>
                <th class="text-right">
                    {{$receipt_details->total_paid}}
                </th>
            </tr>
            @endif
            <!-- Total Due-->
            @if(!empty($receipt_details->total_due))
            <tr>
                <th class="text-justify">
                    {!! $receipt_details->total_due_label !!}:
                </th>
                <th class="text-right">
                    {{$receipt_details->total_due}}
                </th>
            </tr>
            @endif

            @if(!empty($receipt_details->all_due))
            <tr>
                <th class="text-justify">
                    {!! $receipt_details->all_bal_label !!}:
                </th>
                <td class="text-right">
                    {{$receipt_details->all_due}}
                </td>
            </tr>
            @endif
        </table>
    </div>

    <div class="col-xs-6">
        <table class="table table-responsive table-slim table-bordered-print">
            <tbody>
                @if(!empty($receipt_details->total_quantity_label))
                <tr class="color-555">
                    <th class="text-justify" style="width:70%">
                        {!! $receipt_details->total_quantity_label !!}:
                    </th>
                    <th class="text-right">
                        {{$receipt_details->total_quantity}}
                    </th>
                </tr>
                @endif
                <tr>
                    <th class="text-justify" style="width:70%">
                        {!! $receipt_details->subtotal_label !!}
                    </th>
                    <th class="text-right">
                        {{$receipt_details->subtotal}}
                    </th>
                </tr>
                @if(!empty($receipt_details->total_exempt_uf))
                <tr>
                    <th class="text-justify" style="width:70%">
                        @lang('lang_v1.exempt')
                    </th>
                    <th class="text-right">
                        {{$receipt_details->total_exempt}}
                    </th>
                </tr>
                @endif
                <!-- Shipping Charges -->
                @if(!empty($receipt_details->shipping_charges))
                <tr>
                    <th class="text-justify" style="width:70%">
                        {!! $receipt_details->shipping_charges_label !!}
                    </th>
                    <th class="text-right">
                        {{$receipt_details->shipping_charges}}
                    </th>
                </tr>
                @endif

                @if(!empty($receipt_details->packing_charge))
                <tr>
                    <th class="text-justify" style="width:70%">
                        {!! $receipt_details->packing_charge_label !!}
                    </th>
                    <th class="text-right">
                        {{$receipt_details->packing_charge}}
                    </th>
                </tr>
                @endif

                <!-- Discount -->
                @if( !empty($receipt_details->discount) )
                <tr>
                    <th class="text-justify">
                        {!! $receipt_details->discount_label !!}
                    </th>

                    <th class="text-right">
                        (-) {{$receipt_details->discount}}
                    </th>
                </tr>
                @endif

                @if( !empty($receipt_details->reward_point_label) )
                <tr>
                    <th class="text-justify">
                        {!! $receipt_details->reward_point_label !!}
                    </th>

                    <th class="text-right">
                        (-) {{$receipt_details->reward_point_amount}}
                    </th>
                </tr>
                @endif

                <!-- Tax -->
                @if( !empty($receipt_details->tax) )
                    <tr>
                        <th class="text-justify">
                            {!! $receipt_details->tax_label !!}
                        </th>
                        <th class="text-right">
                            (+) {{$receipt_details->tax}}
                        </th>
                    </tr>
                @else
                    <tr>
                        <th class="text-justify">
                            {!! $receipt_details->order_tax_label !!}
                        </th>
                        <th class="text-right">
                            (+) {{$receipt_details->total_transaction_tax}}
                        </th>
                    </tr>
                @endif
                @if( $receipt_details->round_off_amount > 0)
                <tr>
                    <th class="text-justify">
                        {!! $receipt_details->round_off_label !!}
                    </th>
                    <th class="text-right">
                        {{$receipt_details->round_off}}
                    </th>
                </tr>
                @endif

                <!-- Total -->
                <tr>
                    <th class="text-justify total_label">
                        {!! $receipt_details->total_label !!}
                    </th>
                    <th class="text-right total">
                        {{$receipt_details->total}}
                        @if(!empty($receipt_details->total_in_words))
                        <br>
                        <small>({{$receipt_details->total_in_words}})</small>
                        @endif
                    </th>
                </tr>
            </tbody>
        </table>

    </div>
    @if($receipt_details->additional_notes)
    <div class="col-xs-12">
        <p class="text-center"> <label> @lang( 'sale.sell_note' ):</label> {!! nl2br($receipt_details->additional_notes)
            !!}</p>
    </div>
    @endif
</div>

@if($receipt_details->show_barcode)
<div class="row">
    <div class="col-xs-12">
    </div>
</div>
@endif

<div class="row">
    <div class="col-xs-12">
        <div class="col-xs-4">
            <table>
                <thead>
                    <tr>
                        <th colspan="2" class="text-justify">
                            <h3>@lang('business.buyer')</h3>
                        </th>

                    </tr>
                </thead>
                <tr>
                    <th class="text-justify"> <label>@lang('business.name') </th>
                    <th class="text-right">:________________</th>
                </tr>
                <tr>
                    <th class="text-justify"> <label>@lang('business.signature') </th>
                    <th class="text-right">:________________</th>
                </tr>
            </table>
        </div>
        <div class="col-xs-4">
            {{-- QrCode --}}
            <img width="150px" class="center-block" src="{{$receipt_details->qrcode}}" alt="QR Code" />
        </div>
        <div class="col-xs-4">
            <table>
                <thead>
                    <tr>
                        <th colspan="2" class="text-justify">
                            <h3>@lang('business.seller')</h3>
                        </th>

                    </tr>
                </thead>
                <tr>
                    <th class="text-justify"> <label>@lang('business.name') </th>
                    <th class="text-right">:________________</th>
                </tr>
                <tr>
                    <th class="text-justify"> <label>@lang('business.signature') </th>
                    <th class="text-right">:________________</th>
                </tr>
            </table>
        </div>
    </div>
</div>

@if(!empty($receipt_details->footer_text))
<div class="row">
    <div class="col-xs-12">
        {!! $receipt_details->footer_text !!}
    </div>
</div>
@endif