<div class="row" id="featured_products_box" style="">
    @if(!empty($featured_products))
        @include('sale_pos.partials.featured_products')
    @endif
</div>
<div class="row">
    @if(!empty($categories))
        {{--		<div class="col-md-4" id="product_category_div">--}}
        {{--			<select class="select2" id="product_category" style="width:100% !important">--}}

        {{--				<option value="all">@lang('lang_v1.all_category')</option>--}}

        {{--				@foreach($categories as $category)--}}
        {{--					<option value="{{$category['id']}}">{{$category['name']}}</option>--}}
        {{--				@endforeach--}}

        {{--				@foreach($categories as $category)--}}
        {{--					@if(!empty($category['sub_categories']))--}}
        {{--						<optgroup label="{{$category['name']}}">--}}
        {{--							@foreach($category['sub_categories'] as $sc)--}}
        {{--								<i class="fa fa-minus"></i> <option value="{{$sc['id']}}">{{$sc['name']}}</option>--}}
        {{--							@endforeach--}}
        {{--						</optgroup>--}}
        {{--					@endif--}}
        {{--				@endforeach--}}
        {{--			</select>--}}
        {{--        </div>--}}
        <div class="col-12" id="product_category_div">
            <div class="selectgroup mb-2 product-category-filter">

                <div class="margin-left-10 @if(empty($featured_products)) hide @endif" id="feature_product_div">
                    <button type="button" class="btn btn-primary btn-flat" id="show_featured_products" ><i class="fa fa-heart fa-1x" aria-hidden="true"></i></button>
                </div>


                <button value="all" class="selectgroup-button product_category_button margin-left-10" type="button">All</button>
                @foreach($categories as $category)
                    @if(isset($category['image']))
                        <button value="{{$category['id']}}" type="button"  class="product_category_button category-image-button">
                            <img src="{{$category['image_url'] }}" class="img-thumbnail category-image">
                        </button>
                    @else
                        <button value="{{$category['id']}}" class="selectgroup-button product_category_button margin-left-10" type="button">{{$category['name']}}</button>
                    @endif
                    @if(!empty($category['sub_categories']))
                        @foreach($category['sub_categories'] as $sc)
                            <button value="{{$sc['id']}}"  class="selectgroup-button product_category_button margin-left-10" type="button">{{$sc['name']}}</button>
                        @endforeach
                    @endif
                @endforeach
            </div>

            <style>
                .category-image{
                    margin-top: -2px !important;
                }
                .category-image-button{
                    border: none;
                    background-color: inherit;
                }
                .product-category-filter {
                    display: flex;
                    flex-wrap: wrap;
                    margin-right: 0 !important;
                }
                .selectgroup-button {
                    background-color: #fdfdff;
                    border-color: #e4e6fc;
                    border-width: 1px;
                    border-style: solid;
                    display: block;
                    text-align: center;
                    padding: 0 1rem;
                    height: 35px;
                    position: relative;
                    cursor: pointer;
                    border-radius: 3px;
                    user-select: none;
                    font-size: 13px;
                    min-width: 2.375rem;
                    line-height: 36px;
                }
            </style>
        </div>
    @endif

    @if(!empty($brands))
        <div class="col-sm-4 invisible" id="product_brand_div">
            {!! Form::select('size', $brands, null, ['id' => 'product_brand', 'class' => 'select2', 'name' => null, 'style' => 'width:100% !important']) !!}
        </div>
@endif

<!-- used in repair : filter for service/product -->
    <div class="col-md-6 hide" id="product_service_div">
        {!! Form::select('is_enabled_stock', ['' => __('messages.all'), 'product' => __('sale.product'), 'service' => __('lang_v1.service')], null, ['id' => 'is_enabled_stock', 'class' => 'select2', 'name' => null, 'style' => 'width:100% !important']) !!}
    </div>


</div>
<br>
<div class="row">
    <input type="hidden" id="suggestion_page" value="1">
    <div class="col-md-12">
        <div class="eq-height-row" id="product_list_body"></div>
    </div>
    <div class="col-md-12 text-center" id="suggestion_page_loader" style="display: none;">
        <i class="fa fa-spinner fa-spin fa-2x"></i>
    </div>
</div>

<!-- QTY calculator moved to pos_form_actions.blade.php-->


<style>
    .cal-border-bottom{
        border-bottom: 2px solid #666;
    }
    .cal-border-right{
        border-right: 2px solid #666;
    }
</style>
