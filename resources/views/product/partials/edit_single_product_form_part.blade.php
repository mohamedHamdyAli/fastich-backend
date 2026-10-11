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

<div class="col-sm-12">
    <br>
    <div class="table-responsive">
        <table class="table table-bordered add-product-price-table table-condensed {{$class}}">
            <tr>
                <th colspan='2'>@lang('product.default_purchase_price')</th>
                <th class="">@lang('product.profit_percent') @show_tooltip(__('tooltip.profit_percent'))</th>
                <th colspan='2'>@lang('product.default_selling_price')</th>
                <th></th>
                <th class="hide">@lang('lang_v1.product_image')</th>
            </tr>
            @foreach($product_deatails->variations as $variation )
            @if($loop->first)
            <tr>
                <td>
                    <br />
                    <div class="input-group">
                        <span class="input-group-addon"><small>@lang('product.inc_of_tax')</small></span>
                        {!! Form::text('single_dpp_inc_tax', @price_format($variation->dpp_inc_tax), ['class' =>
                        'form-control input-sm dpp input_number', 'id'=>'single_dpp_inc_tax', 'placeholder' =>
                        __('product.inc_of_tax')]); !!}
                    </div>
                </td>
                <td>
                    <br />
                    <div class="input-group">
                        <span class="input-group-addon"><small> @lang('product.exc_of_tax')</small></span>
                        {!! Form::text('default_purchase_price', @price_format($variation->default_purchase_price),
                        ['placeholder' => __('product.exc_of_tax'), 'id' =>'default_purchase_price', 'class' =>
                        'form-control input-sm input_number pp_exc_tax']); !!}
                    </div>
                </td>

                <td class="">
                    <br />
                    {!! Form::text('profit_percent', @num_format($variation->profit_percent), ['class' => 'form-control
                    input-sm input_number', 'id' => 'profit_percent', 'required']); !!}
                </td>

                <td>
                    {!! Form::label('dsp_label', trans('product.inc_of_tax') . ':*') !!}
                    {!! Form::text('single_dsp', @num_format($variation->default_sell_price), ['class' => 'form-control
                    input-sm dsp input_number', 'placeholder' => __('product.exc_of_tax'), 'id' => 'single_dsp',
                    'required']); !!}
                    {!! Form::text('single_dsp_inc_tax', @num_format($variation->sell_price_inc_tax), ['class' =>
                    'form-control input-sm hide input_number', 'placeholder' => __('product.inc_of_tax'), 'id' =>
                    'single_dsp_inc_tax', 'required']); !!}
                </td>

                <td>
                    <input type="hidden" name="single_variation_id" value="{{$variation->id}}">
                    {!! Form::label('single_dpp', trans('product.exc_of_tax')) !!}
                    {!! Form::text('single_dpp', @num_format($variation->default_sell_price), ['class' => 'form-control
                    input-sm dpp input_number', 'placeholder' => __('product.exc_of_tax')]); !!}
                </td>
                <td>
                    <!-- 5- please make this field visible but disabled, the purpose of it is to see the price after VAT, but it confuse users as it is shown as an entry field -->
                    {!! Form::label('applicable_tax', trans('product.applicable_tax') ) !!}
                    {!! Form::text('applicable_tax', @num_format($applicable_tax), ['class' => 'form-control input-sm
                    input_number', 'readonly'=> 'true', 'style'=>'border: none', 'background:none', 'placeholder' =>
                    __('product.applicable_tax')]); !!}

                </td>
                <td class="hide">
                    @php
                    $action = !empty($action) ? $action : '';
                    @endphp
                    @if($action !== 'duplicate')
                    @foreach($variation->media as $media)
                    <div class="img-thumbnail">
                        <span class="badge bg-red delete-media"
                            data-href="{{ action('ProductController@deleteMedia', ['media_id' => $media->id])}}"><i
                                class="fa fa-close"></i></span>
                        {!! $media->thumbnail() !!}
                    </div>
                    @endforeach
                    @endif
                    <div class="form-group">
                        {!! Form::label('variation_images', __('lang_v1.product_image') . ':') !!}
                        {!! Form::file('variation_images[]', ['class' => 'variation_images', 'accept' => 'image/*',
                        'multiple']); !!}
                        <small>
                            <p class="help-block">@lang('purchase.max_file_size', ['size' =>
                                (config('constants.document_size_limit') / 1000000)]) <br>
                                @lang('lang_v1.aspect_ratio_should_be_1_1')</p>
                        </small>
                    </div>
                </td>
            </tr>
            @endif
            @endforeach
        </table>
    </div>
</div>
