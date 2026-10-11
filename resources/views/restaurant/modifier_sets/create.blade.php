<div class="modal-dialog modal-lg product-modifier-modal" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('Restaurant\ModifierSetsController@store'), 'method' => 'post', 'id' => 'table_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'restaurant.add_modifier' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('name', __( 'restaurant.modifier_set' ) . ':*') !!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
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
                                          <div class="form-group">
                                              <select name="modifier_tax[]" class="form-control"  placeholder="Please Select">
                                                
                                              </select>
                                          </div>
                                        </td>
                                        <td>
                                          <div class="form-group">
                                            <select name="modifier_tax_type[]" class="form-control">
                                                <option></option>
                                            </select>
                                          </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <input type="text" name="modifier_price_inc_tax[]" class="form-control" required />
                                            </div>
                                        </td>
                                        ';
                
                                    $html_other_row = $html . '<td>
                                            <button class="btn btn-danger btn-xs pull-right remove-modifier-row" type="button"><i class="fa fa-minus"></i></button>
                                          </td>
                                        </tr>';
                
                                    $html_first_row = $html . "<td>
                                            <button class='btn btn-primary btn-xs pull-right add-modifier-row' type='button'
                                            data-html='{{ $html_other_row }}'>
                                            <i class='fa fa-plus'></i></button>
                                          </td>
                                        </tr>";

                                @endphp


                            </th>
                            <th>@lang( 'restaurant.tax')</th>
                            <th>@lang( 'restaurant.tax_type')</th>
                            <th>@lang( 'restaurant.price_inc_tax')</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <div class="form-group">
                                    <input type="text" name="modifier_name[]"
                                           class="form-control"
                                           placeholder="{{ __( 'lang_v1.name' ) }}" required>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <input type="text" name="modifier_price[]"
                                           class="form-control input_number modifier-price"
                                           placeholder="{{ __( 'lang_v1.price' ) }}" required>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <select name="modifier_tax[]" class="form-control select2 modifier-tax">
                                        <option value="">Please Select</option>
                                        @foreach($taxRates as $taxRate)
                                            <option data-amount="{{ $taxRate['amount'] }}"
                                                    value="{{ $taxRate['id'] }}">{{ $taxRate['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::select('modifier_tax_type[]', ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], 'exclusive',['class' => 'form-control select2 modifier-tax-type', 'required']); !!}
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <input type="text" name="modifier_price_inc_tax[]" readonly
                                           class="form-control input_number modifier-price-inc-tax"
                                           required/>
                                </div>
                            </td>
                            <td>
                                <button class='btn btn-primary btn-xs pull-right add-modifier-row' type='button'>
                                    <i class='fa fa-plus'></i></button>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-xs pull-right remove-modifier-row" type="button"><i
                                            class="fa fa-minus"></i></button>
                            </td>
                        </tr>
                        {{--              {!! $html_first_row !!}--}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
