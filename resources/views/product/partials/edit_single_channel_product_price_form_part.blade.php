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
<div class="table-responsive">
    <table class="table table-bordered add-product-price-table channel table-condensed {{$class}}">
        <tr>
            <th>@lang('product.default_selling_price')</th>
            <th></th>
        </tr>
        <tr>
            <td>
                {!! Form::label('dsp_label', trans('product.inc_of_tax') . ':*') !!}
                {!! Form::text('add_single_dsp', null, ['class' => 'form-control input-sm dsp input_number hide',
                'placeholder' => __('product.exc_of_tax'), 'id' => 'add_single_dsp', 'required']); !!}
                {!! Form::text('add_channel_dsp_inc_tax', null, ['class' => 'form-control input-sm input_number',
                'placeholder' => __('product.inc_of_tax'), 'id' => 'add_channel_dsp_inc_tax', 'required']); !!}
            </td>
            <td>
                <div class="col-sm-6">
                    {!! Form::label('add_channel_single_dpp', trans('product.exc_of_tax')) !!}
                    {!! Form::text('add_channel_single_dpp', null, ['class' => 'form-control input-sm dpp input_number', 'id' =>
                    'add_channel_single_dpp', 'placeholder' => __('product.exc_of_tax')]); !!}
                </div>
                <!-- 5- please make this field visible but disabled, the purpose of it is to see the price after VAT, but it confuse users as it is shown as an entry field -->
                <div class="col-sm-6 ">
                    {!! Form::label('add_channel_applicable_tax', trans('product.applicable_tax') ) !!}
                    {!! Form::text('add_channel_applicable_tax', null, ['class' => 'form-control input-sm input_number',
                    'readonly'=> 'true', 'style'=>'border: none', 'background:none', 'id' => 'add_channel_applicable_tax',
                    'placeholder' => __('product.applicable_tax')]); !!}

                </div>
            </td>
        </tr>
    </table>
</div>
