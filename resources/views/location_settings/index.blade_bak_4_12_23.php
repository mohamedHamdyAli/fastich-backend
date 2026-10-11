@extends('layouts.app')
@section('title', __('messages.business_location_settings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'messages.business_location_settings' ) - {{$location->name}}</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
          <!-- Custom Tabs -->
          <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">@lang('receipt.receipt_settings')</a></li>
                @if(in_array('kitchen',$business->enabled_modules))
                    <li class=""><a href="#tab_2" data-toggle="tab" aria-expanded="true">{{__( 'messages.kitchen_station')}}</a></li>
                @endif
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="tab_1">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>@lang( 'receipt.receipt_settings')
                                <small>@lang( 'receipt.receipt_settings_mgs')</small>
                            </h4>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            {!! Form::open(['url' => route('location.settings_update', [$location->id]), 'method' => 'post', 'id' => 'bl_receipt_setting_form']) !!}

                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('print_receipt_on_invoice', __('receipt.print_receipt_on_invoice') . ':') !!}
                                @show_tooltip(__('tooltip.print_receipt_on_invoice'))
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-file-alt"></i>
                                    </span>
                                    {!! Form::select('print_receipt_on_invoice', $printReceiptOnInvoice, $location->print_receipt_on_invoice, ['class' => 'form-control select2', 'required']); !!}
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('receipt_printer_type', __('receipt.receipt_printer_type') . ':*') !!} @show_tooltip(__('tooltip.receipt_printer_type'))
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-print"></i>
                                    </span>
                                    {!! Form::select('receipt_printer_type', $receiptPrinterType, $location->receipt_printer_type, ['class' => 'form-control select2', 'required']); !!}
                                </div>
                                @if(config('app.env') == 'demo')
                                    <span class="help-block">Only Browser based option is enabled in demo.</span>
                                @endif

                            </div>
                        </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('number_of_thermal_print_copy', __('printer.number_of_thermal_print_copy') . ':*') !!}
                                    <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-share-alt"></i>
                                    </span>
                                        <input type="text" name="number_of_thermal_print_copy" class="form-control"
                                               placeholder="Enter Number of thermal print Copy"
                                               value="{{ $location->number_of_thermal_print_copy  }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-4" id="location_printer_div">
                                <div class="form-group">
                                    {!! Form::label('printer_id', __('printer.receipt_printers') . ':*') !!}
                                    <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-share-alt"></i>
                                    </span>
                                        {!! Form::select('printer_id', $printers, $location->printer_id, ['class' => 'form-control select2', 'required']); !!}
                                    </div>
                                </div>
                            </div>
                            {{--                        <div class="clearfix"></div>--}}
                            <br/>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('invoice_layout_id', __('invoice.invoice_layout') . ':*') !!} @show_tooltip(__('tooltip.invoice_layout'))
                                    <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-info"></i>
                                    </span>
                                        {!! Form::select('invoice_layout_id', $invoice_layouts, $location->invoice_layout_id, ['class' => 'form-control select2', 'required']); !!}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':*') !!} @show_tooltip(__('tooltip.invoice_scheme'))
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-info"></i>
                                    </span>
                                    {!! Form::select('invoice_scheme_id', $invoice_schemes, $location->invoice_scheme_id, ['class' => 'form-control select2', 'required']); !!}
                                </div>
                            </div>
                        </div>


                        

                        <div class="row">
                            <div class="col-sm-12">
                                <button class="btn btn-primary pull-right" type="submit">@lang('messages.update')</button>
                            </div>
                        </div>
                        {!! Form::close() !!}
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="tab_2">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>{{__('messages.kitchen_stations')}}
                                <small>{{__('messages.feature_for_restaurant')}}</small>
                            </h4>
                        </div>
                    </div>
                    <br>

                    <div class="row">
                        <div class="col-md-12">
                            {!! Form::open(['url' => route('location.settings_update', [$location->id]), 'method' => 'post', 'id' => 'bl_receipt_setting_form']) !!}
                            <div class="col-sm-4 margin-left-10">
                                <div class="form-group">
                                    <div class="checkbox icheck">
                                        <label>
                                            <input type="checkbox" name="is_active_kitchen_location" {{ $location->is_active_kitchen_station == 1 ? 'checked' : '' }}> {{__('messages.enable_kitchen_station')}} 
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12" style="margin-top: 10px;padding-left: 0px;">
                                <div class="col-sm-5 form-group" style="padding-left: 0px;">
{{--                                    <label>How many station this business location have?</label>--}}
{{--                                    {!! Form::number('kitchen_station', null, ['class' => 'form-control','min' => 1]); !!}--}}
                                    <button class="btn btn-primary btn-modal" id="AddStation">{{__('messages.add_station')}}</button>
                                    <br><br><br><label>{{__('messages.enter_station_name')}}</label>
                                </div>
                            </div>  
                            <div class="col-sm-12" style="margin-top: 0px;padding-left: 0px;" id="StationDiv">
                                @if($location->kitchenStation()->count() < 1)
                                <div class="col-sm-4 form-group" style="padding-left: 0px;display: flex" id="kitchenStation-0">
                                    <input type="text" name="station_name[]" class="form-control" placeholder="Station Name">
                                    <a class="removeStation" data-id="0"><i class="fa fa-trash text-danger margin-left-10"></i></a>
                                </div>
                                @else
                                    @foreach($kitchenStations as $key => $kitchenStation)
                                    <div class="col-sm-4 form-group" style="padding-left: 0px;display: flex" id="kitchenStation-{{$kitchenStation->id}}">
                                        <input type="text" name="station_name_existing[{{$kitchenStation->id}}]" class="form-control" placeholder="Station Name" value="{{$kitchenStation->station_name}}">
                                        <a class="btn-delete" data-id="{{$kitchenStation->id}}"><i class="fa fa-trash text-danger margin-left-10"></i></a>
                                    </div>
                                    @endforeach 
                                @endif
                            </div>    
                            
                            <div class="row">
                                <div class="col-sm-12">
                                    <button class="btn btn-primary pull-right" type="submit">@lang('messages.update')</button>
                                </div>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.tab-content -->
          </div>
          <!-- nav-tabs-custom -->
        </div>
    </div>
	
    <div class="modal fade invoice_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade invoice_edit_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
@section('javascript')
    <script type="text/javascript">
        let Id = 0;
        $(document).on('click','#AddStation', function(){
            Id = Id + 1;
            $('#StationDiv').append('<div class="col-sm-4 form-group" style="padding-left: 0px;display: flex" id="kitchenStation-'+ Id +'">\n' +
                '                                    <input type="text" name="station_name[]" class="form-control" placeholder="Station Name">\n' +
                '                                    <a class="btn-delete" data-id="'+ Id +'"><i class="fa fa-trash text-danger margin-left-10"></i></a>\n' +
                '                                </div>')
        }); 
        $(document).on('click','.removeStation', function(){
            let id = $(this).attr('data-id');
            $('#kitchenStation-'+id).remove();
        });
        $(document).on('click','.btn-delete', function(){
            let id = $(this).attr('data-id');
            $(this).closest('#kitchenStation-'+id).remove();
        });
    </script>
@endsection
