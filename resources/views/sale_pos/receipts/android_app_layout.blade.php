<?php
$allModifierTax = 0;
$modifierName = [];
?>
<!-- business information here -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- <link rel="stylesheet" href="style.css"> -->
    <title>Receipt-{{$receipt_details->invoice_no}}</title>
</head>
<body>
<div style="font-family: Playfair Display, serif;">
    {{--    <div style="width: 100%; padding: 25px;">--}}
    <div style="width: 100%;">
        <table style="width:100%;">
            <tbody>
            <tr>
                <th style="font-weight:unset; width: 50%">{{ \Carbon\Carbon::now()->format('d-m-Y') }}</th>
                <th style="font-weight:unset; width: 50%">POS- الك شاورما</th>
            </tr>
            </tbody>
        </table>
        <br/>
        <table style="width:100%;margin-bottom:5px;">
            @if(!empty($receipt_details->header_text))
                <div style="font-weight:unset;" class="headings">{!! $receipt_details->header_text !!}</div>
                <br/>
            @endif
        </table>
        <table style="width:100%;">
            <tbody style="width:100%;">
            <tr style="width:100%;">
                <td style="text-align:start; width:100%;">
                    <p style="margin: 0">
                        <b style="font-size: 18px;text-transform: uppercase;"> {{ $receipt_details->location_name }} </b>
                        <br/>
                        {{ $receipt_details->address }}
                        <br/>
                        <b style="font-size: 14px;">VAT:</b> 123456789vat
                        <br/>
                        <b style="font-size: 15px;">{{ $receipt_details->invoice_heading }}</b>
                    </p>
                    <hr style="border:1px solid #000000; margin-bottom: 0; margin-top: 15px;"/>
                </td>
            </tr>
            </tbody>
        </table>
        <table style="width:100%; margin-bottom:5px;">
            <tbody style="width:100%;">
            <tr>
                <th style="text-align: start; font-weight: unset; width: 100%; padding: 0;"><b>
                        {{ $receipt_details->invoice_no_prefix }}</b></th>
                <th></th>
                <th style="text-align: end; font-weight: unset; padding: 0;">{{ $receipt_details->invoice_no }}</th>
            </tr>
            </tbody>
        </table>
        <table style="width: 100%; margin-bottom: 10px;">
            <tbody style="width: 100%;">
            <tr>
                <th style="text-align: start; font-weight: unset; padding: 0;"><b>{{ $receipt_details->date_label }}</b>
                </th>
                <td style="text-align: end; font-weight: unset; padding: 0; white-space: nowrap;">
                    {{ $receipt_details->invoice_date }}</td>
            </tr>
            </tbody>
        </table>
        <div class="product-data">
            <table style="width: 100%; margin-bottom: 5px;">
                <tbody style="width: 100%;">
                <tr>
                    <th style="border-bottom: 2.5px dashed #b3b2b2; padding-left: 5px;">#</th>
                    <th style="width: 45%; padding: 5px; border-bottom: 2.5px dashed #b3b2b2; text-align: start;">
                        Product
                    </th>
                    <th style="text-align: right; padding: 0; border-bottom: 2.5px dashed #b3b2b2;">
                        Quantity
                    </th>
                    <th style="text-align: right; padding: 0; border-bottom: 2.5px dashed #b3b2b2;">
                        Unit Price
                    </th>
                    <th style="text-align: right; padding: 0; border-bottom: 2.5px dashed #b3b2b2;">
                        Subtotal
                    </th>
                </tr>
                @foreach($receipt_details->lines as $index => $product)
                    <tr key="{{ $index }}">
                        <td>{{$index + 1}}</td>
                        <td style="font-size: 14px;">{{ $product['name'] }}</td>
                        <td style="text-align: end; padding: 0;">{{ $product['quantity'] }} {{ $product['units'] }}</td>
                        @php
                            $productUnitPrice = number_format(floor($product['unit_price']*100)/100,2, '.', '');
                            $productRoundedPrice = $productUnitPrice + ceil($product['single_product_tax'] * 100) / 100;
                            $totalPriceOfProduct = $productRoundedPrice * $product['quantity'];
                        @endphp
                        <td style="text-align: end; padding: 0;">{{ number_format($productRoundedPrice, 2) }}</td>
                        <td style="text-align: end; padding: 0;">
                            {{ number_format($totalPriceOfProduct, 2) }}
                        </td>
                    </tr>
                    @if(isset($product['modifiers']))
                        @foreach($product['modifiers'] as $key => $modifier)
                            @if($modifier['sell_price_inc_tax'] > 0)
                                <tr key="{{ $key }}">
                                    <td></td>
                                    <td style="font-size: 13px;">{{ $modifier['variation'] }}</td>
                                    <td style="text-align: end; padding: 0; font-size: 14px;">
                                        {{ $modifier['quantity'] }} {{ $modifier['units'] }}
                                    </td>
                                    <td style="text-align: end; padding: 0; font-size: 14px;">
                                        @php
                                            $modifierUnitPrice = number_format(floor($modifier['unit_price_inc_tax']*100)/100,2, '.', '');
                                            $totalTax = 0;
                                            $modifierQty = $modifierUnitPrice * $product['quantity'];
                                            $totalTax = $modifierQty *  $modifier['tax_amount'] / 100; 
                                            $calTax = ceil($totalTax * 100) / 100;
                                            $allModifierTax = $allModifierTax + $calTax;
                                            $modifierRoundedPrice = $modifierUnitPrice + $calTax;
                                        @endphp
                                        {{ number_format($modifierRoundedPrice, 2) }}
                                    </td>
                                    <td style="text-align: end; padding: 0; font-size: 14px;">
                                        {{ number_format($modifierRoundedPrice *  $modifier['quantity'], 2) }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        {!!  html_entity_decode(getModifierName($product['modifiers']))  !!}
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <hr style="border: 1px solid black; margin-top: 15px;"/>
        <div class="payment-data">
            <table style="width: 100%;">
                <tbody style="width: 100%;">
                <tr>
                    <th style="text-align: right; width: 65%; font-size: 16px; padding: 0;">{{ $receipt_details->subtotal_label }}
                    </th>
                    <th style="text-align: end; font-size: 18px; padding: 0;">{{ number_format($receipt_details->final_total - ($allModifierTax + countTotalTax($receipt_details)), 2). '﷼ '}}
                    </th>
                </tr>
                <tr>
                    <td style="text-align: right; width: 65%;">Tax
                    </td>
                    <td style="text-align: end;"> (+) {{ number_format($allModifierTax + countTotalTax($receipt_details), 2) }}
                         ﷼
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; width: 65%;"><b
                                style="font-size: 16px;">{{ $receipt_details->total_label }}</b>
                    </td>
                    <td style="text-align: end;">
                        <b> {{ $receipt_details->total }}</b></td>
                </tr>
                @foreach($receipt_details->payments as $index => $payment)
                    <tr key="{{ $index }}">
                        <td style="text-align: right; font-size: 15px; width: 65%;">{{ $payment['method']}}{{($payment['date'])}}
                        </td>
                        <td style="text-align: end;">{{ $payment['amount']}}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="text-align: right; width: 65%; font-size: 15px;"> {{ $receipt_details->total_paid_label }}</td>
                    <td style="text-align: end;">{{ $receipt_details->total_paid }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; width: 65%; font-size: 15px;">{{ $receipt_details->total_due_label }}</td>
                    <td style="text-align: end;">{{ $receipt_details->total_due }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <hr style="border: 1px solid #000000;"/>
        <table style="width: 100%;">
            <div style="font-weight: unset;">
                {!! $receipt_details->footer_text !!}
            </div>
        </table>
        <table style="width: 100%;">
            <tbody style="width: 100%;">
            <tr>
                <td>
                    <p style="text-align: center; margin: 0;"> {!!   $receipt_details->additional_notes !!}</p>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
<script>
    let products = JSON.parse(JSON.stringify(@JSON($receipt_details)));

    function toFixedTrunc (x, n) {
        const abc = typeof x === 'string' ? x : x.toString();
        const v = abc.split('.');
        if (n <= 0) return v[0];
        let f = v[1] || '';
        if (f.length > n) return `${v[0]}.${f.substr(0, n)}`;
        while (f.length < n) f += '0';
        return `${v[0]}.${f}`;
    }

    function loadFreeModifiers (modifiers) {
        let modifierText = [];
        modifiers.forEach((modifier) => {
            if (+modifier.sell_price_inc_tax === 0) {
                modifierText.push(modifier.variation);
            }
        });

        if (modifierText.length === 0) {
            return '';
        }

        return (
            `<tr>
                <td> </td>
                <td style="font-size: 14px;">${modifierText.join(',')}</td>
                <td style="text-align: end; padding:0;"> </td>
                <td style="text-align: end; padding:0;"> </td>
                <td style="text-align: end; padding:0;"> </td>
            </tr>`
        );
    }

    function countTotalTax (tax) {
        let totalAmount = 0;

        tax && tax.appliedTax.forEach(cartItem => {
            totalAmount = totalAmount + (Math.ceil(cartItem.tax_amount * 100) / 100);
        });

        return +totalAmount.toFixed(2);
    }

    let totalProductTax = countTotalTax(products);
    let totalModifierTax = products.modifier_tax;
    let taxAmount = totalProductTax + totalModifierTax;

    let productTr = '';
    let modifierTr = '';
    let modifierText = [];
    let allModifierTax = 0;

    // Product and Modifier Data
    products.lines && products.lines.map((line, index) => {
        const unitPriceRounded = toFixedTrunc(line.unit_price, 4);
        const roundedValue = +unitPriceRounded + line.single_product_tax;
        const subTotalFinal = +roundedValue.toFixed(2) * line.quantity;
        productTr =
            `<tr key=${index}>
                <td>${index + 1}</td>
                <td>${line.name}</td>
                <td style="text-align: end; padding: 0;">${line.quantity} ${line.units}</td>
                <td style="text-align: end; padding: 0;">${+roundedValue.toFixed(2)}</td>
                <td style="text-align: end; padding: 0;">
                    ${subTotalFinal.toFixed(2)}
                </td>
            </tr>
            `;
        {
            line.modifiers && line.modifiers.length !== 0 ?
                line.modifiers.map((modifier, index) => {
                    if (+modifier.sell_price_inc_tax === 0) {
                        modifierText.push(modifier.variation);
                    }

                    const modifierPrice = toFixedTrunc(modifier.unit_price_inc_tax, 4);
                    let calTax = 0;
                    if (modifier.tax_id && modifier.tax_id > 0) {
                        calTax = modifierPrice * modifier.tax_amount / 100;
                        allModifierTax = allModifierTax + (calTax.toFixed(2) * line.quantity);
                    }
                    const roundedValue =  (+modifierPrice + calTax).toFixed(2);
                    if (+modifier.sell_price_inc_tax > 0) {
                        modifierTr +=
                            `<tr key=${index}>
                                    <td> </td>
                                    <td style="font-size: 14px;">${modifier.variation}</td>
                                    <td style="text-align: end; padding: 0; font-size: 14px;">
                                        ${modifier.quantity} ${modifier.units}
                                    </td>
                                    <td style="text-align: end; padding: 0; font-size: 14px;">
                                        ${roundedValue}
                                    </td>
                                    <td style="text-align: end; padding: 0; font-size: 14px;">
                                        ${(roundedValue * modifier.quantity).toFixed(2)}
                                    </td>
                                </tr>
                                `;
                    }
                }) : null;
        }
        modifierTr += line.modifiers && line.modifiers.length !== 0 && loadFreeModifiers(line.modifiers);
    });

    $('.product-data1').append(`
        <table style="width: 100%; margin-bottom: 5px;">
            <tbody style="width: 100%;">
                <tr>
                    <th style="border-bottom: 2.5px dashed #b3b2b2; padding-left: 5px;">#</th>
                    <th style="width: 45%; padding: 5px; border-bottom: 2.5px dashed #b3b2b2;">
                        Product
                    </th>
                    <th style="text-align: right; padding: 0; border-bottom: 2.5px dashed #b3b2b2;">
                        Quantity
                    </th>
                    <th style="text-align: right; padding: 0; border-bottom: 2.5px dashed #b3b2b2;">
                        Unit Price
                    </th>
                    <th style="text-align: right; padding: 0; border-bottom: 2.5px dashed #b3b2b2;">
                        Subtotal
                    </th>
                </tr>
                ${productTr}
                ${modifierTr}
            </tbody>
        </table>
    `);

    $('.payment-data1').append(`
     <table style="width: 100%;">
            <tbody style="width: 100%;">
                <tr>
                    <th style="text-align: right; width: 65%; font-size: 18px; padding: 0;">${products.subtotal_label}
                    </th>
                    <th style="text-align: end; font-size: 18px; padding: 0;">${(products.final_total -
        (allModifierTax + totalProductTax)).toFixed(2) + '﷼ '}
                    </th>
                </tr>
                <tr>
                    <td style="text-align: right; width: 65%;">Tax
                    </td>
                    <td style="text-align: end;">(+) ﷼ ${allModifierTax + totalProductTax}</td>
                </tr>
                <tr>
                    <td style="text-align: right; width: 65%;"><b
                                style="font-size: 18px;">${products.total_label}</b>
                    </td>
                    <td style="text-align: end;">
                        <b>${products.total}</b></td>
                </tr>
                ${
        products.payments && products.payments.length !== 0 ? products.payments.map((payment, index) => {
            return (`<tr key=${index}>
                            <td style="text-align: right; font-size: 15px; width: 65%;">${payment.method}(${payment.date})
                            </td>
                            <td style="text-align: end;">${payment.amount}</td>
                        </tr>`);
        }) : null
    }
                <tr>
                    <td style="text-align: right; width: 65%;">${products.total_paid_label}</td>
                    <td style="text-align: end;">${products.total_paid}</td>
                </tr>
                <tr>
                    <td style="text-align: right; width: 65%;">${products.total_due_label}</td>
                    <td style="text-align: end;">${products.total_due} </td>
                </tr>
            </tbody>
        </table>
    `);
</script>
</body>
</html>

<style type="text/css">
    .f-8 {
        font-size: 8px !important;
    }

    @media print {
        /*@page*/
        /*{*/
        /*    size: 8.5in 5.5in;*/
        /*}*/
        * {
            font-size: 12px;
            font-family: 'Times New Roman';
            word-break: break-all;
        }

        .f-8 {
            font-size: 8px !important;
        }

        .headings {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .sub-headings {
            font-size: 15px !important;
            font-weight: 700 !important;
        }

        .border-top {
            border-top: 1px solid #242424;
        }

        .border-bottom {
            border-bottom: 1px solid #242424;
        }

        .border-bottom-dotted {
            border-bottom: 1px dotted darkgray;
        }

        td.serial_number, th.serial_number {
            width: 5%;
            max-width: 5%;
        }

        td.description,
        th.description {
            width: 35%;
            max-width: 35%;
            word-break: break-all;
        }

        td.quantity,
        th.quantity {
            width: 15%;
            max-width: 15%;
            word-break: break-all;
        }

        td.unit_price, th.unit_price {
            width: 25%;
            max-width: 25%;
            word-break: break-all;
        }

        td.price,
        th.price {
            width: 20%;
            max-width: 20%;
            word-break: break-all;
        }

        .centered {
            text-align: center;
            align-content: center;
        }

        .ticket {
            width: 100%;
            max-width: 100%;
            page-break-after: always;
        }

        img {
            max-width: inherit;
            width: auto;
        }

        .hidden-print,
        .hidden-print * {
            display: none !important;
        }
    }

    .table-info {
        width: 100%;
    }

    .table-info tr:first-child td, .table-info tr:first-child th {
        padding-top: 8px;
    }

    .table-info th {
        text-align: left;
    }

    .table-info td {
        text-align: right;
    }

    .logo {
        float: left;
        width: 35%;
        padding: 10px;
    }

    .text-with-image {
        float: left;
        width: 65%;
    }

    .text-box {
        width: 100%;
        height: auto;
    }

    .textbox-info {
        clear: both;
    }

    .textbox-info p {
        margin-bottom: 0px
    }

    .flex-box {
        display: flex;
        width: 100%;
    }

    .flex-box p {
        width: 50%;
        margin-bottom: 0px;
        white-space: nowrap;
    }

    .table-f-12 th, .table-f-12 td {
        font-size: 12px;
        word-break: break-word;
    }

    .bw {
        word-break: break-word;
    }
</style>
