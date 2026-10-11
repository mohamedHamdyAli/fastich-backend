<!-- Modal -->
<div class="modal fade" id="addCustomPriceModal" tabindex="-1" role="dialog" aria-labelledby="addCustomPriceModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalLongTitle">     {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help')) </h3>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <div class="form-group">
          {!! Form::label('product_locations', __('business.business_locations')  . ':*') !!}
              {!! Form::select('custom_product_locations[]', $business_locations, null, ['class' => 'form-control select2 Location', 'multiple', 'id' => 'custom_product_locations']); !!}
          </div>
          <div  id="custom_product_price_form_part"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"> @lang('lang_v1.close') </button>
        <button type="button" class="btn btn-primary" id="save_custom_price"> @lang('lang_v1.save_changes')</button>
      </div>
    </div>
  </div>
</div>
