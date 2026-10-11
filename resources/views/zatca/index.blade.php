@extends('layouts.app')

@section('content')
@php
if($business!=null)
$class = 'disabled';
else
$class = '';
@endphp
<div class="container">
    <h2>ZATCA E-Invoice Integration
        @if($business!=null)
        <span class="badge btn-success">Registered</span>
        @endif
    </h2>
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif
    @if($business!=null)
    <!-- Button to request for production certificate renewal
        This button will launch a model, to ask for OTP that user will generate from ZATCA portal
        and make api call to ZATCA to renew the production certificate 
    -->
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#renewProductionCertificate">
        Renew Production Certificate
    </button>
    @endif

    <form method="POST" action="{{ route('zatca.saveBusinessDetails') }}">
        @csrf
        <!-- Meta Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Metadata</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="uuid">UUID</label>
                            <input type="text" class="form-control" id="uuid" name="uuid" value="{{ old('uuid', $uuid ?? '') }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="business_id">Business ID:</label>
                            <input type="text" class="form-control" id="business_id" name="business_id" value="{{ $business_id }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="egs_id">EGS Id</label>
                            <input type="text" class="form-control" id="egs_id" name="egs_id"
                                value="{{ old('egs_id', $business->egs_id ?? ('EGS' . $business_id)) }}" readonly>

                            <x-input-error field="egs_id" />
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- Business Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Business Information
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registered_business_name">Registered Business Name(English)</label>
                            <input type="text" class="form-control" id="registered_business_name" maxlength="64"
                                name="registered_business_name" value="{{ old('registered_business_name', $business->registered_business_name ?? '') }}" required>
                            <x-input-error field="registered_business_name" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registered_business_name_ar">Registered Business Name(Arabic)</label>
                            <input type="text" class="form-control" id="registered_business_name_ar" dir='rtl' maxlength="64"
                                name="registered_business_name_ar" value="{{ old('registered_business_name_ar', $business->registered_business_name_ar ?? '') }}" required>
                            <x-input-error field="registered_business_name_ar" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="common_name">Common Name</label>
                            <input type="text" class="form-control" id="common_name"
                                name="common_name" value="{{ old('common_name', $business->common_name ?? '') }}" required>
                            <x-input-error field="common_name" />
                        </div>
                    </div>
                </div>
                <div class="row mt-3">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vat_number">VAT Identification Number (VAT)</label>
                            <input type="text" class="form-control" id="vat_number" name="vat_number"
                                value="{{ old('vat_number', $business->vat_number ?? '') }}" required>
                            <x-input-error field="vat_number" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="crn_number">CRN Number</label>
                            <input type="text" class="form-control" id="crn_number" name="crn_number"
                                value="{{ old('crn_number', $business->crn_number ?? '') }}">
                            <x-input-error field="crn_number" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="business_category">Business Category</label>
                            <input type="text" class="form-control" id="business_category" name="business_category"
                                value="{{ old('business_category', $business->business_category ?? '') }}">
                            <x-input-error field="business_category" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Address Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registered_address">Registered Address</label>
                            <input type="text" class="form-control" id="registered_address" name="registered_address"
                                value="{{ old('registered_address', $business->registered_address ?? '') }}">
                            <x-input-error field="registered_address" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="city_name">City</label>
                            <input type="text" class="form-control" id="city_name" name="city_name"
                                value="{{ old('city_name', $business->city_name ?? '') }}">
                            <x-input-error field="city_name" />
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="city_subdivision_name">City Subdivision</label>
                            <input type="text" class="form-control" id="city_subdivision_name" name="city_subdivision_name"
                                value="{{ old('city_subdivision_name', $business->city_subdivision_name ?? '') }}">
                            <x-input-error field="city_subdivision_name" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="street_name">Street Name</label>
                            <input type="text" class="form-control" id="street_name" name="street_name"
                                value="{{ old('street_name', $business->street_name ?? '') }}">
                            <x-input-error field="street_name" />
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="postal_zone">Postal Zone</label>
                            <input type="text" class="form-control" id="postal_zone" name="postal_zone"
                                value="{{ old('postal_zone', $business->postal_zone ?? '') }}">
                            <x-input-error field="postal_zone" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="building_number">Building Number</label>
                            <input type="text" class="form-control" id="building_number" name="building_number"
                                value="{{ old('building_number', $business->building_number ?? '') }}">
                            <x-input-error field="building_number" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>OTP</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="otp">Enter your OTP from Zatca Portal</label>
                            <input type="text" class="form-control" id="otp" name="otp"
                                value="{{ old('otp', '') }}">
                            <x-input-error field="otp" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary {{ $class }}">Generate Sample E-Invoices & Process Registration</button>
    </form>
</div>

<!-- Modal -->

<!-- Modal -->
<div class="modal fade" id="renewProductionCertificate" tabindex="-1" role="dialog" aria-labelledby="renewProductionCertificateLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="renewProductionCertificateLabel">Renew Production Certificate</h3>
            </div>
            <div class="modal-body">
                <div id="modal-error-message" class="alert alert-danger" style="display: none;"></div>
                <p>Before you proceed, please ensure you have generated the OTP from Zatca Portal</p>
                <div class="form-group">
                    <label for="renewal_otp">Enter your OTP from Zatca Portal</label>
                    <input type="number" class="form-control" id="renewal_otp" name="renewal_otp" required>
                    <x-input-error field="renewal_otp" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="renewCertSubmit">Renew Production Certificate</button>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {

        $('#renewCertSubmit').on('click', function() {
            const button = $(this);
            button.prop('disabled', true);
            
            $('#modal-error-message').hide();
            const otp = $('#renewal_otp').val();
            const csrfToken = '{{ csrf_token() }}';

            $.ajax({
                url: '{{ route("zatca.renewProductionCertificate") }}',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    renewal_otp: otp
                },
                success: function(response) {
                    if (response.success) {
                        $('#renewProductionCertificate').modal('hide');
                        location.reload();
                    } else {
                        $('#modal-error-message').text(response.error).show();
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    let errorMessage = 'An error occurred. Please try again.';
                    if (response && response.message) {
                        errorMessage = response.message;
                    }
                    $('#modal-error-message').text(errorMessage).show();
                    button.prop('disabled', false);
                }
            });
        });
    });
</script>