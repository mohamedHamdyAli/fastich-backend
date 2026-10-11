<!-- Modal -->
@if(!session('business.enable_price_tax'))
@php
$default = 0;
$class = 'hide';
@endphp
@else
@php
$default = null;
$class = '';
@endphp
@endif
<div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="exampleModalLongTitle"> @lang('lang_v1.update_custom_price') :
                ({{$custom_product_price->location_name}} ) </h2>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">

            <!-- <div class="form-group col-sm-12" id="edit_custom_product_price_form_part"></div> -->
            <div class="table-responsive">
                <table class="table table-bordered add-product-price-table table-condensed {{$class}}">
                    <tr>
                        <th>@lang('product.default_selling_price')</th>
                        <th></th>
                    </tr>
                    <tr>
                        <td>
                            {!! Form::label('dsp_label', trans('product.inc_of_tax') . ':*') !!}
                            {!! Form::text('edit_single_dsp', $custom_product_price->custom_price, ['class' =>
                            'form-control input-sm dsp input_number hide', 'placeholder' => __('product.exc_of_tax'),
                            'id' => 'edit_single_dsp', 'required']); !!}
                            {!! Form::text('edit_single_dsp_inc_tax', $custom_product_price->custom_price, ['class' =>
                            'form-control input-sm input_number', 'placeholder' => __('product.inc_of_tax'), 'id' =>
                            'edit_single_dsp_inc_tax', 'required']); !!}
                        </td>
                        <td>
                            <div class="col-sm-6 ">
                                {!! Form::label('edit_applicable_tax', trans('product.applicable_tax') ) !!}
                                {!! Form::text('edit_applicable_tax',
                                $custom_product_price->custom_price_applicable_tax, ['class' => 'form-control input-sm
                                input_number', 'readonly'=> 'true', 'style'=>'border: none', 'background:none', 'id' =>
                                'edit_applicable_tax', 'placeholder' => __('product.applicable_tax')]); !!}

                                <label class="hide">@lang('product.inc_of_tax')</label>
                                {!! Form::text('edit_single_dpp_inc_tax', $custom_product_price->custom_price, ['class'
                                => 'form-control input-sm dpp input_number hide ', 'readonly'=> 'true',
                                'style'=>'border: none', 'id' => 'edit_single_dpp_inc_tax', 'placeholder' =>
                                __('product.inc_of_tax')]); !!}
                            </div>
                            <div class="col-sm-6">
                                {!! Form::label('edit_single_dpp', trans('product.exc_of_tax')) !!}
                                {!! Form::text('edit_product_id', $custom_product_price->product_id, ['class' =>
                                'form-control input-sm hide', 'id' => 'edit_product_id', 'required']); !!}
                                {!! Form::text('edit_business_id', $custom_product_price->business_id, ['class' =>
                                'form-control input-sm hide', 'id' => 'edit_business_id', 'required']); !!}
                                {!! Form::text('edit_single_dpp', $custom_product_price->custom_price_exc_tax, ['class'
                                => 'form-control input-sm dpp input_number', 'id' => 'edit_single_dpp', 'placeholder' =>
                                __('product.exc_of_tax')]); !!}
                            </div>
                            <!-- 5- please make this field visible but disabled, the purpose of it is to see the price after VAT, but it confuse users as it is shown as an entry field -->

                        </td>
                    </tr>

                </table>

            </div>
        </div>
        <div class="modal-footer">
            <div class="col-sm-4 text-left">
                <a href="{{ url('products/delete_product_custom_price' , [ 'product_id' => $custom_product_price->product_id , 'business_id' => $custom_product_price->business_id ]) }}"
                    class="delete-product-custom-price"> @lang('messages.remove') </a>
            </div>
            <div class="col-sm-8 text-right">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"> @lang('lang_v1.close') </button>
                <button type="button" class="btn btn-primary" id="save_edit_custom_price">
                    @lang('lang_v1.save_changes')</button>
            </div>
        </div>
    </div>
