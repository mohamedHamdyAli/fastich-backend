<!-- Modal -->
<div class="modal-dialog" role="document">
    <div class="modal-content">
      {!! Form::open(['url' => action('LocationSettingsController@addCategoryKitchens', [$location_id,$kitchenID]), 'method' => 'POST', 'id' => 'assign_unassign_kitchen_form', 'enctype' => 'multipart/form-data' ]) !!}
      <div class="modal-header">
        <h3 class="modal-title" id="addKitchenCategoryModalTitle">  @lang('lang_v1.add_kitchen_categories') </h3>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
         {!! Form::hidden('location_id', $location_id, ['class' => 'form-control']); !!}
          {!! Form::hidden('kitchenID', $kitchenID, ['class' => 'form-control']); !!}
          {!! Form::select('kitchen_categories_ids[]', $kitchen_categories_dropdown,  $kitchenStationsIDS  , ['class' => 'form-control select2 ', 'multiple', 'id' => 'kitchen_categories_ids']); !!}
      </div>
      <div class="modal-footer">
         <button type="submit" class="btn btn-primary">@lang('lang_v1.save_changes')</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"> @lang('lang_v1.close') </button>
      </div>
      {!! Form::close() !!}
    </div>
</div>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
