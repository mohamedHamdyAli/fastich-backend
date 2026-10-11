<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title hide" id="modalTitle">{{$recipe->variation->full_name}}</h4>

    </div>
    <div class="modal-body">

        <div class="row">
            <div class="col-md-12">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr class="bg-gray">
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
                            <td><span >{{$ingredient_price}}</span>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-right">
                                <strong>@lang('manufacturing::lang.ingredients_cost')</strong></td>
                            <td><span >{{$ingredient_total_price}}</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
