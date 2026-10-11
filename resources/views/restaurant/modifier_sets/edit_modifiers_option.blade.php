<div class="modal-dialog modal-lg product-modifier-modal" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('Restaurant\ModifierSetsController@update', [$modifer_set->id]), 'method' => 'PUT', 'id' => 'edit_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'restaurant.edit_modifier' )</h4>
        </div>

        <div class="modal-body">

            <div class="row">

                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('name', __( 'restaurant.modifier_set' ) . ':*') !!}
                        {!! Form::text('name', $modifer_set->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
                    </div>
                </div>

                <div class="col-sm-12">
                    <h4>@lang( 'restaurant.modifiers' )</h4>
                </div>

                <div class="col-sm-12">
                    <table class="table table-condensed" id="add-modifier-table">
                        <thead>
                        <tr>
                            <th>@lang( 'restaurant.modifier')</th>
                            <th>
                                @lang( 'lang_v1.price')

                                @php
                                    $html = '<tr><td>
                                          <div class="form-group">
                                            <input type="text" name="modifier_name[]" 
                                            class="form-control" 
                                            placeholder="' . __( 'lang_v1.name' ) . '" required>
                                          </div>
                                        </td>
                                        <td>
                                          <div class="form-group">
                                            <input type="text" name="modifier_price[]" class="form-control input_number" 
                                            placeholder="' . __( 'lang_v1.price' ) . '" required>
                                          </div>
                                        </td>
                                        <td>
                                          <button class="btn btn-danger btn-xs pull-right remove-modifier-row" type="button"><i class="fa fa-minus"></i></button>
                                        </td>
                                        </tr>';
                                @endphp
                            </th>
                            <th>@lang( 'restaurant.tax')</th>
                            <th>@lang( 'restaurant.tax_type')</th>
                            <th>@lang( 'restaurant.price_inc_tax')</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($modifer_set->variations as $modifier)
                            <tr>
                                <td>
                                    <div class="form-group">
                                        <input type="text" name="modifier_name_edit[{{$modifier->id}}]"
                                               class="form-control" value="{{$modifier->name}}"
                                               placeholder="@lang( 'lang_v1.name' )"
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" name="modifier_price_edit[{{$modifier->id}}]"
                                               class="form-control input_number modifier-price-edit"
                                               value="{{$modifier->default_sell_price}}"
                                               placeholder="@lang( 'lang_v1.price' )" required>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <select name="modifier_tax_edit[{{$modifier->id}}]"
                                                class="form-control select2 modifier-tax-edit">
                                            <option value="">Please Select</option>
                                            @foreach($taxRates as $taxRate)
                                                <option data-amount="{{ $taxRate['amount'] }}"
                                                        value="{{ $taxRate['id'] }}" {{ ($modifier->tax_id == $taxRate['id']) ? 'selected' : '' }} >{{ $taxRate['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <select name="modifier_tax_type_edit[{{$modifier->id}}]"
                                                class="form-control select2 modifier-tax-type-edit">
                                            <option value="exclusive" {{ ($modifier->tax_type == 'exclusive') ? 'selected' : ''  }}>
                                                Exclusive
                                            </option>
                                            <option value="inclusive" {{ ($modifier->tax_type == 'inclusive') ? 'selected' : ''  }}>
                                                Inclusive
                                            </option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" value="{{ number_format($modifier->sell_price_inc_tax, 2) }}"
                                               name="modifier_price_inc_tax_edit[{{$modifier->id}}]"
                                               class="form-control modifier-price-inc-tax-edit input_number"
                                               required/>
                                    </div>
                                </td>
                                <td>
                                    @if(!$loop->first)
                                        <button class="btn btn-danger btn-xs pull-right remove-modifier-row"
                                                type="button"><i
                                                    class="fa fa-minus"></i></button>
                                    @else
                                        <button class="btn btn-primary btn-xs pull-right add-modifier-row-edit"
                                                type="button">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div>
</div>
<!-- /.modal-dialog -->
