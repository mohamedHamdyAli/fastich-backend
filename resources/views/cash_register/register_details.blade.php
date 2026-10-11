<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h3 class="modal-title">@lang( 'cash_register.register_details' ) (
                {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} -
                {{\Carbon::createFromFormat('Y-m-d H:i:s', $close_time)->format('jS M, Y h:i A')}} )</h3>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <table class="table">
                        <tr>
                            <th colSpan="2" style="text-align:center;">
                                @lang('lang_v1.payments'):
                            </th>
                        </tr>
                        <tr>
                            <td>
                                @lang('cash_register.cash_payment'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_cash }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                @lang('cash_register.card_payment'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_card }}</span>
                            </td>
                        </tr>
                        @if(!empty($register_details->clientAppArray))
                        @foreach ($register_details->clientAppArray as $client_app)
                        @if($client_app['total_prepaid_value'] != 0)
                        <tr>
                            <td>
                                {{ $client_app['app_prepaid_title'] ? $client_app['app_prepaid_title'] : 0.00  }}
                            </td>
                            <td>
                                <span className="display_currency"
                                    data-currency_symbol="true">{{ $client_app['total_prepaid_value'] ? $client_app['total_prepaid_value'] : 0.00  }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        @if($client_app['total_cash_value'] != 0)
                        <tr>
                            <td>
                                {{ $client_app['app_cash_title'] ? $client_app['app_cash_title'] : 0.00  }}
                            </td>
                            <td>
                                <span className="display_currency"
                                    data-currency_symbol="true">{{ $client_app['total_cash_value'] ? $client_app['total_cash_value'] : 0.00  }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        @if($client_app['total_card_value'] != 0)
                        <tr>
                            <td>
                                {{ $client_app['app_card_title'] ? $client_app['app_card_title'] : 0.00  }}
                            </td>
                            <td>
                                <span className="display_currency"
                                    data-currency_symbol="true">{{ $client_app['total_card_value'] ? $client_app['total_card_value'] : 0.00  }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        @endif
                        <tr class="">
                            <th>
                                @lang('lang_v1.total_payment'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->total_payment_new ? $register_details->total_payment_new : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <tr class="">
                            <th>
                                @lang('cash_register.total_refund'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->total_refund_cash_new ? ($register_details->total_refund_cash_new) : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <tr class="">
                            <th>
                                @lang('lang_v1.net_payment'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->net_payment_new ? ($register_details->net_payment_new) : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <!--  Net payments by type -->
                        <tr>
                            <th colSpan="2" style="text-align:center;">
                                @lang('lang_v1.net_payments_by_type'):
                            </th>
                        </tr>
                        <tr class="">
                            <th>
                                @lang('lang_v1.total_cash'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->all_cash_new ? ($register_details->all_cash_new) : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <tr class="">
                            <th>
                                @lang('lang_v1.total_card'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->all_card_new ? ($register_details->all_card_new) : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <tr class="">
                            <th>
                                @lang('lang_v1.prepaid_app'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->total_prepaid ? ($register_details->total_prepaid) : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <tr class="">
                            <th>
                                @lang('lang_v1.total_discount'):
                            </th>
                            <td>
                                <b>
                                    <span class="display_currency" data-currency_symbol="true">
                                        {{ $register_details->total_discount ? ($register_details->total_discount) : 0.00  }}
                                    </span>
                                </b>
                            </td>
                        </tr>
                        <!-- Monetary -->
                        <tr>
                            <th colSpan="2" style="text-align:center;">
                                @lang('lang_v1.monetary_custody'):
                            </th>
                        </tr>
                        <tr class="success">
                            <td>
                                @lang('lang_v1.cash_in_hand'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->cash_in_hand ? ($register_details->cash_in_hand) : 0.00   }}</span>
                            </td>
                        </tr>
                        <tr class="success">
                            <td>
                                @lang('lang_v1.total_expense'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_expense ? ($register_details->total_expense) : 0.00 }}</span>
                            </td>
                        </tr>
                        <tr class="success">
                            <td>
                                @lang('lang_v1.remainder_cash_in_hand'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{  $register_details->remainder_cash_in_hand ? ($register_details->remainder_cash_in_hand) : 0.00 }}</span>
                            </td>
                        </tr>

                        <!-- Cash Details -->
                        <tr>
                            <th colSpan="2" style="text-align:center;">
                                @lang('lang_v1.cash_details'):
                            </th>
                        </tr>
                        <tr class="info">
                            <td>
                                @lang('lang_v1.cash_in_register'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->all_cash_new ? ($register_details->all_cash_new) : 0.00   }}</span>
                            </td>
                        </tr>

                        <tr class="info">
                            <td>
                                @lang('lang_v1.entered_cash_amount'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{  $register_details->input_cash_in_hand ? ($register_details->input_cash_in_hand) : 0.00 }}</span>
                            </td>
                        </tr>
                        <tr class="{{$register_details->cash_overage_shortage_class }}">
                            <td>
                                {{$register_details->cash_overage_shortage_label ? ($register_details->cash_overage_shortage_label) : null }}
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->cash_overage_shortage ? ($register_details->cash_overage_shortage) : 0.00 }}</span>
                            </td>
                        </tr>

                        <!-- Closing Note  -->

                        <tr>
                            <th colSpan="2" style="text-align:center;">
                                @lang('lang_v1.closing_note')
                            </th>
                        </tr>
                        <tr class="">
                            <td colSpan="2">
                                <p style="text-align: center;">{{  $register_details->closing_note }}</p>
                            </td>
                        </tr>

                        <!-- Hidden  -->
                        <tr class="hide">
                            <th>
                                @lang('lang_v1.credit_sales'):
                            </th>
                            <td>
                                <b><span class="display_currency"
                                        data-currency_symbol="true">{{ $details['transaction_details']->total_sales - $register_details->total_sale }}</span></b>
                            </td>
                        </tr>
                        <tr class="hide">
                            <th>
                                @lang('cash_register.total_sales'):
                            </th>
                            <td>
                                <b><span class="display_currency"
                                        data-currency_symbol="true">{{ $details['transaction_details']->total_sales }}</span></b>
                            </td>
                        </tr>
                        <tr class="hide">
                            <td>
                                @lang('cash_register.checque_payment'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_cheque }}</span>
                            </td>
                        </tr>

                        <tr class="hide">
                            <td>
                                @lang('cash_register.bank_transfer'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_bank_transfer }}</span>
                            </td>
                        </tr>
                        <tr class="hide">
                            <td>
                                @lang('lang_v1.advance_payment'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_advance }}</span>
                            </td>
                        </tr>
                        @if(array_key_exists('custom_pay_1', $payment_types))
                        <tr class="hide">
                            <td>
                                {{$payment_types['custom_pay_1']}}:
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_custom_pay_1 }}</span>
                            </td>
                        </tr>
                        @endif
                        @if(array_key_exists('custom_pay_2', $payment_types))
                        <tr class="hide">
                            <td>
                                {{$payment_types['custom_pay_2']}}:
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_custom_pay_2 }}</span>
                            </td>
                        </tr>
                        @endif
                        @if(array_key_exists('custom_pay_3', $payment_types))
                        <tr class="hide">
                            <td>
                                {{$payment_types['custom_pay_3']}}:
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_custom_pay_3 }}</span>
                            </td>
                        </tr>
                        @endif
                        <tr class="hide">
                            <td>
                                @lang('cash_register.other_payments'):
                            </td>
                            <td>
                                <span class="display_currency"
                                    data-currency_symbol="true">{{ $register_details->total_other }}</span>
                            </td>
                        </tr>
                        <tr class="hide">
                            <th>
                                @lang('cash_register.total_refund')
                            </th>
                            <td>
                                <b><span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_refund }}</span></b><br>
                                <small>
                                    @if($register_details->total_cash_refund != 0)
                                    Cash: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span><br>
                                    @endif
                                    @if($register_details->total_cheque_refund != 0)
                                    Cheque: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_cheque_refund }}</span><br>
                                    @endif
                                    @if($register_details->total_card_refund != 0)
                                    Card: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_card_refund }}</span><br>
                                    @endif
                                    @if($register_details->total_bank_transfer_refund != 0)
                                    Bank Transfer: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_bank_transfer_refund }}</span><br>
                                    @endif
                                    @if($register_details->total_advance_refund != 0)
                                    @lang('lang_v1.advance'): <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_advance_refund }}</span><br>
                                    @endif
                                    @if(array_key_exists('custom_pay_1', $payment_types) &&
                                    $register_details->total_custom_pay_1_refund != 0)
                                    {{$payment_types['custom_pay_1']}}: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_custom_pay_1_refund }}</span>
                                    @endif
                                    @if(array_key_exists('custom_pay_2', $payment_types) &&
                                    $register_details->total_custom_pay_2_refund != 0)
                                    {{$payment_types['custom_pay_2']}}: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_custom_pay_2_refund }}</span>
                                    @endif
                                    @if(array_key_exists('custom_pay_3', $payment_types) &&
                                    $register_details->total_custom_pay_3_refund != 0)
                                    {{$payment_types['custom_pay_3']}}: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_custom_pay_3_refund }}</span>
                                    @endif
                                    @if($register_details->total_other_refund != 0)
                                    Other: <span class="display_currency"
                                        data-currency_symbol="true">{{ $register_details->total_other_refund }}</span>
                                    @endif
                                </small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @include('cash_register.register_product_details')

            <div class="row">
                <div class="col-xs-6">
                    <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
                    <b>@lang('business.email'):</b> {{ $register_details->email}}<br>
                    <b>@lang('business.business_location'):</b> {{ $register_details->location_name}}<br>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" aria-label="Print"
                onclick="$(this).closest('div.modal').printThis();">
                <i class="fa fa-print"></i> @lang( 'messages.print' )
            </button>

            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.cancel' )
            </button>
        </div>

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->