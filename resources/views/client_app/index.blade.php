@extends('layouts.app')
@section('title', __('business.clientApp'))




@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'client_app.client_app' )
        <small>@lang( 'client_app.manage_your_client_app' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">

    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'client_app.all_your_client_app' )])

    @can('client_app.create')
    @slot('tool')
    <div class="box-tools">
        <p class="hide" style="color: #4169e1;">Note: @lang( 'client_app.max_client_app' )</p>
        <button type="button" class="btn btn-block btn-primary btn-modal"
            data-href="{{action('ClientAppController@create')}}" data-container=".client_app_modal">
            <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
    </div>
    @endslot
    @endcan
    @can('client_app.view')
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="client_app_table">
            <thead>
                <tr>
                    <th>@lang( 'client_app.client_name' )</th>
                    <th>@lang( 'messages.action' )</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcan
    @endcomponent

    <div class="modal fade client_app_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>


</section>
<!-- /.content -->
@endsection
