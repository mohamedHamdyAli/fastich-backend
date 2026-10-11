<!-- Modal -->
<div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <!-- Content Header (Page header) -->
            <h3 class="modal-title" id="exampleModalLongTitle"> @lang('manufacturing::lang.add_ingredients') </h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <!-- Main content -->
            <section class="content">
                <div id="box_group">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h4 class="box-title"><strong>@lang('sale.product'):
                                </strong>{{$variation->product_name}} @if($variation->product_type == 'variable') -
                                {{$variation->product_variation_name}} - {{$variation->name}} @endif</h4>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-10 col-md-offset-1">
                                    <div class="form-group">
                                        {!! Form::label('search_product',
                                        __('manufacturing::lang.select_ingredient').':') !!}

                                        {!! Form::text('search_product', null, ['class' => 'form-control', 'id' =>
                                        'search_product', 'placeholder' =>
                                        __('manufacturing::lang.select_ingredient'), 'autofocus' => true ]); !!}

                                        {!! Form::hidden('variation_id', $variation->id, ['id' => 'variation_id']);
                                        !!}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table table-striped table-th-green text-center ingredients_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('manufacturing::lang.ingredient')</th>
                                                <th>@lang('manufacturing::lang.final_quantity')</th>
                                                <th>@lang('lang_v1.price')</th>
                                                <th>&nbsp;</th>
                                            </tr>
                                        </thead>
                                        <tbody class="ingredient-row-sortable">
                                            @php
                                            $row_index = 0;
                                            $ingredient_groups = [];
                                            $ingredient_total = 0;
                                            @endphp
                                            @if(!empty($ingredients))
                                            @foreach($ingredients as $ingredient)
                                            @php
                                            $ingredient_obj = (object) $ingredient;
                                            $price = !empty($ingredient_obj->quantity) ? $ingredient_obj->quantity *
                                            $ingredient_obj->default_purchase_price : $ingredient_obj->default_purchase_price;
                                            $price = $price * $ingredient_obj->multiplier;
                                            $ingredient_total += $price;
                                            @endphp
                                            @if(empty($ingredient['mfg_ingredient_group_id']))
                                            @php
                                            $row_index = $loop->index;
                                            @endphp

                                            @include('product.recipe.ingredient_row', ['ingredient' => (object)
                                            $ingredient, 'ig_index' => ''])

                                            @php
                                            $row_index++;
                                            @endphp
                                            @else
                                            @php
                                            $ingredient_groups[$ingredient['mfg_ingredient_group_id']][] =
                                            $ingredient;
                                            @endphp
                                            @endif
                                            @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--box end-->
                    @php
                    $ig_index = 0;
                    @endphp
                    @foreach($ingredient_groups as $ingredient_group)
                    @php
                    $ig_name = !empty($ingredient_group[0]['ingredient_group_name']) ?
                    $ingredient_group[0]['ingredient_group_name'] : '';
                    $ig_description = !empty($ingredient_group[0]['ig_description']) ?
                    $ingredient_group[0]['ig_description'] : '';
                    @endphp
                    @include('product.recipe.ingredient_group', ['ingredients' => $ingredient_group, 'ig_index' =>
                    $ig_index, 'ig_name' => $ig_name, 'ig_description' => $ig_description])
                    @php
                    $ig_index++;
                    $row_index += count($ingredient_group);
                    @endphp
                    @endforeach
                </div>
                <div class="box box-solid">
                    <div class="box-body">
                        <div class="row">
                            <input type="hidden" id="row_index" value="{{$row_index}}">
                            <input type="hidden" id="ig_index" value="{{$ig_index}}">
                            <div class="col-md-12 text-center">
                                <strong>@lang('manufacturing::lang.ingredients_cost'): </strong> <span
                                    id="ingredients_cost_text">{{@num_format($ingredient_total)}}</span>
                                <input type="hidden" name="total_ingredients_cost" id="ingredients_cost"
                                    value="{{$recipe->ingredients_cost ?? 0}}">
                            </div>
                        </div>
                    </div>
                </div>

            </section>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal"> @lang('lang_v1.close') </button>
            <button type="button" class="btn btn-primary pull-right" id="save_recipe">@lang('messages.save')</button>
        </div>
    </div>
</div>
