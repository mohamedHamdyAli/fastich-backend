<!-- Modal -->
<div class="modal fade" id="addModifiersModal" tabindex="-1" role="dialog" aria-labelledby="addModifiersModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalLongTitle">  @lang('lang_v1.add_modifiers') </h3>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          {!! Form::select('modifiers_ids[]', $modifiers_dropdown,  $modifier_products_ids  , ['class' => 'form-control select2 ', 'multiple', 'id' => 'modifiers_ids']); !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"> @lang('lang_v1.close') </button>
        <button type="button" class="btn btn-primary" id="save_modifiers"> @lang('lang_v1.save_changes')</button>
      </div>
    </div>
  </div>
</div>
