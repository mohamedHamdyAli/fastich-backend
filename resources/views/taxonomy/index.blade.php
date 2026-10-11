@extends('layouts.app')
@php
    $heading = !empty($module_category_data['heading']) ? $module_category_data['heading'] : __('category.categories');
    $navbar = !empty($module_category_data['navbar']) ? $module_category_data['navbar'] : null;
@endphp
@section('title', $heading)

@section('content')
@if(!empty($navbar))
    @include($navbar)
@endif
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{$heading }}
        <small>
            {{ $module_category_data['sub_heading'] ?? __( 'category.manage_your_categories' ) }}
        </small>
        @if(isset($module_category_data['heading_tooltip']))
            @show_tooltip($module_category_data['heading_tooltip'])
        @endif
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @php
        $cat_code_enabled = isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code'] ? false : true;
    @endphp
    <input type="hidden" id="category_type" value="{{request()->get('type')}}">
    @component('components.widget', ['class' => 'box-solid'])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action('TaxonomyController@create')}}?type={{request()->get('type')}}"
                    data-container=".category_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="category_table">
                    <thead>
                        <tr>
                        <th>@lang( 'lang_v1.image' )</th>
                            <th>@if(!empty($module_category_data['taxonomy_label'])) {{$module_category_data['taxonomy_label']}} @else @lang( 'category.category' ) @endif</th>
                            @if($cat_code_enabled)
                                <th>{{ $module_category_data['taxonomy_code_label'] ?? __( 'category.code' )}}</th>
                            @endif
                            <th> @lang('lang_v1.assign_unassign_locations') </th>
                            <th> @lang('lang_v1.assign_unassign_modifiers') </th>
                            <th>@lang( 'restaurant.modifier_sets' )</th>
                            <th>@lang( 'lang_v1.product' )</th>
                            <th>@lang( 'lang_v1.created_at' )</th>
                            <th>@lang( 'lang_v1.updated_at' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>

            </div>
    @endcomponent

    <div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">  </div>

    <div class="modal fade addCategoryModifiersModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <div class="modal fade addLocationsModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>



</section>
<!-- /.content -->
@stop
@section('javascript')
    <script>
        let defaultImage = "{{ asset('/img/default.png') }}";
        function myFunction(categoryModifiersID){
            $('#categoryID').val(categoryModifiersID);
            // $.ajax({
            //     url: '/taxonomies/loadModifiersTable/'+categoryModifiersID,
            //     dataType : 'json',
            //     success: function(result){

            //         if(result){
            //             var html ='';
            //             html += '<input type="text" name="categoryID" id="categoryID" value="'+categoryModifiersID+'"/> ';

            //             $('#addCategoryModifiersModal').modal('show');

            //             //$('#addCategoryModifiersModal .modal-body').html(html);

            //             var elmnt = $('#modifiers_ids') ;
            //             alert(elmnt);
            //             document.getElementById("modifiers_ids").options.selectedIndex = 124;
            //             // for(var i=0; i < elmnt.options.length; i++)
            //             //     {
            //             //         if(elmnt.options[i].value === value) {
            //             //         elmnt.selectedIndex = i;
            //             //         break;
            //             //         }
            //             //     }

            //         } else {

            //         }
            //     }

            // });
        }

        $(function () {
            // $('#save_category_modifiers').on('click', function () {
            //     var ids = $('#modifiers_ids').val();
            //     var categoryID = $('#categoryID').val();

            //     $.ajax({
            //         url: '/taxonomies/add_modifiers/'+categoryID,
            //         data: {
            //         modifiers_ids: ids,
            //         },
            //         dataType : 'json',
            //         success: function(result){
            //             if(result.success == true){
            //                 $('#addCategoryModifiersModal').modal('hide');
            //                 toastr.success(result.msg);
            //                 //modifier_table.ajax.reload();
            //                 //location.reload();
            //             } else {
            //                 toastr.error(result.msg);
            //             }
            //         }
            //         });

            // });

      //Brands table
    //   var modifier_table = $('#modifier_table').DataTable({
    //           searching: false,
    //           paging: false,
    //           info: false,
    //           processing: true,
    //           serverSide: true,
    //           ajax: '/products/loadModifiersTable/'+product_id,
    //           columnDefs: [ {
    //               "targets": [1],
    //               "orderable": false,
    //               "searchable": false
    //           } ],
    //           columns: [
    //               { data: 'name', name: 'name'  },
    //               { data: 'variations', name: 'variations'},

    //           ],
    //   });
  });
    </script>
@includeIf('taxonomy.taxonomies_js')
@endsection
