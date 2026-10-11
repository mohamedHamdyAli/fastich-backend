<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title text-center"><strong>@lang('sale.product'): </strong>{{$recipe->variation->name}}</h4>

        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    {!! $recipe->variation->product->product_description !!}
                </div>
                <div class="col-md-6">
                    @foreach($recipe->variation->media as $media)
                    {!! $media->thumbnail([60, 60], 'img-thumbnail') !!}
                    @endforeach
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>@lang('manufacturing::lang.ingredient')</th>
                                <th>@lang('lang_v1.quantity')</th>
                                <th>@lang('lang_v1.price')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $ingredient_groups = [];
                            $ingredient_total_price = 0;
                            @endphp
                            @foreach($ingredients as $ingredient)
                            @php
                            $ingredient_price =
                            $ingredient['quantity']*$ingredient['default_purchase_price']*$ingredient['multiplier'];
                            $ingredient_total_price += $ingredient_price;
                            @endphp
                            @if(empty($ingredient['mfg_ingredient_group_id']))
                            <tr>
                                <td>
                                    {{$ingredient['full_name']}}
                                </td>
                                <td>
                                    <span class="display_currency"
                                        data-currency_symbol="false">{{@format_quantity($ingredient['quantity'])}}</span>
                                    {{$ingredient['unit']}}
                                </td>

                                <td><span >{{@price_format($ingredient_price)}}</span></td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right">
                                    <strong>@lang('manufacturing::lang.ingredients_cost')</strong>
                                </td>
                                <td><span >{{@price_format($ingredient_total_price)}}</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" aria-label="Print"
                onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang(
                'messages.print' )
            </button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close'
                )</button>
        </div>
    </div>
</div>
