@extends('layouts.app')
@section('title', 'Deliverect Integration')

@section('content')
    @php
        // Determine the status text and its corresponding CSS class
        $statusText = $deliverect['status'] ? 'Connected' : 'Not Connected';
        $statusClass = $deliverect['status'] ? 'text-success' : 'text-muted';
        // Construct the header HTML
        $header =
            '<div style="display: flex; justify-content: space-between "><h4 >Deliverect Integration 
                <small><span class="' .
            $statusClass .
            '">' .
            $statusText .
            '</span></small>
              </h4><button class="btn btn-primary" id="sync_channels">Sync Channels</button></div>';
    @endphp
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Deliverect
            <small>Integrate Deliverect with Fastich</small>
            <input type="hidden" id="business_id" value="{{ $business_id }}">
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Success Message -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        <!-- Error Message -->
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{ session('error') }}
            </div>
        @endif

        @component('components.widget', ['class' => 'box-primary', 'header' => $header])
            {!! Form::open(['route' => 'deliverect.onboarding', 'method' => 'post']) !!}
            <div class="row">
                <!-- Client ID Field -->
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('client_id', 'Client ID' . ':*') !!}
                        {!! Form::text('client_id', old('client_id', $deliverect['status'] ? $deliverect['client_id'] : ''), [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => 'Deliverect Client ID',
                        ]) !!}
                        @if ($errors->has('client_id'))
                            <span class="text-danger">{{ $errors->first('client_id') }}</span>
                        @endif
                    </div>
                </div>

                <!-- Client Secret Field -->
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('client_secret', 'Client Secret' . ':*') !!}
                        {!! Form::password('client_secret', [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => 'Deliverect Client Secret',
                        ]) !!}
                        @if ($errors->has('client_secret'))
                            <span class="text-danger">{{ $errors->first('client_secret') }}</span>
                        @endif
                    </div>
                </div>

                <div class="clearfix"></div>
            </div>
            <!-- Submit Button -->

            <div class="row">
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-primary">
                        {{ $deliverect['status'] ? 'Update Integration' : 'Connect Deliverect' }}
                    </button>
                </div>
            </div>
            {!! Form::close() !!}
        @endcomponent


        @if ($deliverect['status'])

            @component('components.widget', ['class' => 'box-primary', 'title' => 'Registration Urls'])
                {!! Form::open(['id' => 'location_mapping_form', 'method' => 'post']) !!}
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('registration_url', 'Registration URL' . ':') !!}
                            {!! Form::text('registration_url', '', [
                                'class' => 'form-control',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 form-group">
                        {!! Form::label('deliverect_loc_id', 'Deliverect Locatoion Id' . ':*') !!}
                        {!! Form::text('deliverect_loc_id', old('deliverect_loc_id', ''), [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => 'Deliverect Location ID',
                        ]) !!}
                        @if ($errors->has('deliverect_loc_id'))
                            <span class="text-danger">{{ $errors->first('deliverect_loc_id') }}</span>
                        @endif
                    </div>
                </div>
                {{-- <div class="row">
                    <div class="col-sm-12">
                        <button id="mapping_submit" type="submit" class="btn btn-primary">
                            Create Mapping
                        </button>
                    </div>
                </div> --}}
                {!! Form::close() !!}
            @endcomponent
        @endif

    </section>
    <!-- /.content -->

@endsection


@section('javascript')
    <script>
        $(document).ready(function() {
            // Get the selected value of the `location_id` dropdown
            const selectedLocation = $('#location_id').val();
            updateRegistrationUrl();
            updateLocationMapping();
            $('#location_id').on('change', function() {
                updateRegistrationUrl();
                updateLocationMapping();
            });

            $('#location_mapping_form').on('submit', function(event) {
                event.preventDefault();
                mapDeliverectLocationWithFastich();
            });

        });

        $('#sync_channels').on('click', function() {
            const $button = $(this);
            $button.prop('disabled', true); // Disable button immediately
            const businessId = $('#business_id').val();
            $.ajax({
                url: `/api/deliverect/${businessId}/sync-channels`,
                method: 'GET',
                complete: function() {
                    $button.prop('disabled', false); // Re-enable button after request completes
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error('Error: ' + response.message);

                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Sync channels failed' + error);
                }
            });
        });

        function updateRegistrationUrl() {
            const locationId = $('#location_id').val();
            const businessId = $('#business_id').val();
            // if any value is null or undefined console error

            const url = `/api/deliverect/${businessId}/${locationId}/get-registration-url`
            $.ajax({
                method: "GET",
                url: url,
                success: function(result) {
                    $('#registration_url').val(result['registrationURL'])
                },
            });
        }

        function updateLocationMapping() {
            const locationId = $('#location_id').val()
            const businessId = $('#business_id').val();
            const url = `/api/deliverect/${businessId}/${locationId}/get-location-mapping`
            $.ajax({
                method: "GET",
                url: url,
                success: function(result) {
                    console.log(result);
                    if (result.success) {
                        let deliverect_loc_id = result['data']['deliverect_loc_id'];
                        $('#deliverect_loc_id').val(result['data']['deliverect_loc_id']);
                        if (deliverect_loc_id) {
                            
                            // $('#mapping_submit').attr('disabled', 'disabled');
                        }  else {
                            // $('#mapping_submit').removeAttr('disabled');
                        }
                        // diable the mapping_submit button
                    } else {
                        $('#deliverect_loc_id').val('');
                        // $('#mapping_submit').removeAttr('disabled');
                    }
                },
            });

        }



        function mapDeliverectLocationWithFastich() {
            const locationId = $('#location_id').val()
            const businessId = $('#business_id').val();
            const deliverectLocId = $('#deliverect_loc_id').val()
            const url = `/api/deliverect/${businessId}/${locationId}/map-deliverect-location`
            $.ajax({
                method: "POST",
                url: url,
                data: {
                    deliverect_loc_id: deliverectLocId
                },
                success: function(result) {
                    // remove the disabled attr from the mapping_submit
                    $('#mapping_submit').removeAttr('disabled');
                    $('#location_mapping_form')[0].reset();
                    updateRegistrationUrl();
                    console.log(result);
                    // Optionally, display a success message or handle the response
                    if (result.success) {
                        toastr.success(result.message);
                    } else {
                        toastr.error('Error: ' + result.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#mapping_submit').removeAttr('disabled');
                    console.error('AJAX Error:', error);
                    toastr.error('An error occurred while processing your request.');
                }
            });
        }
    </script>
@endsection
