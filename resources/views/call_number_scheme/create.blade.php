<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('CallNumberSchemeController@store'), 'method' => 'put', 'id' => 'call_number_scheme_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'invoice.edit_call_number' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            {!! Form::label('name', __( 'invoice.name' ) . ':*') !!}
              {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'invoice.name' ) ]); !!}
          </div>
        </div>
        <div id="invoice_format_settings">
        <div class="clearfix">
        <div class="col-sm-6">
          <div class="form-group">
            {!! Form::label('total_digits', __( 'invoice.total_digits' ) . ':') !!}
            <div class="input-group col-md-12 col-sm-12">
              <span class="input-group-addon">
                  <i class="fa fa-info"></i>
              </span>
              {!! Form::select('total_digits', ['3' => '3','4' => '4', '5' => '5', '6' => '6', '7' => '7', 
              '8' => '8', '9'=>'9', '10' => '10'], null, ['class' => 'form-control', 'required']); !!}
            </div>
          </div>
        </div>
         <div class="col-sm-6">
          <div class="form-group">
            {!! Form::label('last_call_number_end', __( 'invoice.last_call_number_end' ) . ':') !!}
            <div class="input-group col-md-12 col-sm-12">
              <span class="input-group-addon">
                  <i class="fa fa-info"></i>
              </span>
                {!! Form::number('last_call_number_end', 999, ['class' => 'form-control', 'required', 'min' => 0 ]); !!}
            </div>
          </div>
        </div>
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