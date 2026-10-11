<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('Restaurant\ModifierSetsController@createCopy', [$modifer_set->id]), 'method' => 'post', 'id' => 'table_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

    </div>

    <div class="modal-body">
      <div class="row">
          <div class="col-sm-12 text-center">
              <div class="form-group">
                 <h4 class="modal-title">@lang( 'restaurant.create_duplicate_modifiers' ): <span class="text-success">{{$modifer_set->name}}</span>  </h4>

                 <div class="form-group">
                        {!! Form::label('name', __( 'restaurant.modifier_set' ) . ':*') !!}
                        {!! Form::text('name', $modifer_set->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
                    </div>
              </div>
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
                                        <input type="text" name="modifier_name[{{$modifier->id}}]"
                                               class="form-control" value="{{$modifier->name}}"
                                               placeholder="@lang( 'lang_v1.name' )"
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input type="text" name="modifier_price[{{$modifier->id}}]"
                                               class="form-control input_number modifier-price-edit"
                                               value="{{$modifier->default_sell_price}}"
                                               placeholder="@lang( 'lang_v1.price' )" required>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <select name="modifier_tax[{{$modifier->id}}]"
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
                                        <select name="modifier_tax_type[{{$modifier->id}}]"
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
                                               name="modifier_price_inc_tax[{{$modifier->id}}]"
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
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
  $(document).ready(function(){
    $( "#search_product" ).autocomplete({
      source: function(request, response) {
        $.getJSON("/products/list-no-variation", { term: request.term }, response);
      },
      minLength: 2,
      appendTo: "#table_add_form",
      response: function(event,ui) {
        if (ui.content.length == 1)
        {
          ui.item = ui.content[0];
          if(ui.item.qty_available > 0){
            $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
            $(this).autocomplete('close');
          }
        } else if (ui.content.length == 0) {
          swal(LANG.no_products_found)
              .then((value) => {
            $('input#search_product').select();
          });
        }
      },
      select: function( event, ui ) {
        add_product_row(ui.item.product_id);
      }
  })
  .autocomplete( "instance" )._renderItem = function( ul, item ) {
    var string =  "<div>" + item.name;
    string += ' (' + item.sku + ')' + "</div>";
    return $( "<li>" ).append(string).appendTo( ul );
  };
});

function add_product_row(product_id){
  $.ajax({
    method: "GET",
    url: '/modules/product-modifiers/product-row/' + product_id,
    dataType: "html",
    success: function(result){
      $('table#add-modifier-table').append(result);
    }
  });
}
</script>
