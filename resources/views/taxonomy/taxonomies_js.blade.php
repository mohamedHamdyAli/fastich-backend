<script type="text/javascript">
    $(document).ready( function() {

        function getTaxonomiesIndexPage () {
            var data = {category_type : $('#category_type').val()};
            $.ajax({
                method: "GET",
                dataType: "html",
                url: '/taxonomies-ajax-index-page',
                data: data,
                async: false,
                success: function(result){
                    $('.taxonomy_body').html(result);
                }
            });
        }

        function initializeTaxonomyDataTable() {
            //Category table
            if ($('#category_table').length) {
                var category_type = $('#category_type').val();
                category_table = $('#category_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '/taxonomies?type=' + category_type,
                    columns: [
                        // { data: 'image', name: 'image',
                        //     render: function( data, type, full, meta ) {
                        //         return "<img src=\"" + data + "\" height=\"50\"/>";
                        //     }
                        // },
                        { data: 'image', name: 'image' },
                        { data: 'name', name: 'name' },

                        @if($cat_code_enabled)
                            { data: 'short_code', name: 'short_code' },
                        @endif
                        { data: 'assign_unassign_locations', name: 'assign_unassign_locations', orderable: false, searchable: false},
                        { data: 'assign_unassign_modifiers', name: 'assign_unassign_modifiers', orderable: false, searchable: false},
                        { data: 'modifiersSet', name: 'modifiersSet', orderable: false, searchable: false},
                        { data: 'productCount', name: 'productCount', orderable: false, searchable: false},
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'updated_at', name: 'updated_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false},
                    ],
                });
            }
        }

        @if(empty(request()->get('type')))
            getTaxonomiesIndexPage();
        @endif

        initializeTaxonomyDataTable();
    });

    // upload image
    window.displayPhoto = function (input, selector) {
        let displayPreview = true;
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                let image = new Image();
                image.src = e.target.result;
                image.onload = function () {
                    $(selector).attr('src', e.target.result);
                    displayPreview = true;
                };
            };
            if (displayPreview) {
                reader.readAsDataURL(input.files[0]);
                $(selector).show();
            }
        }
    };

    $(document).on('change', '#edit_upload_image', function (){
        displayPhoto(this, '#editCategoryImagePreview');
    });

    $(document).on('change', '#upload_image', function (){
        $('#categoryImagePreview').removeAttr('style');
        displayPhoto(this, '#categoryImagePreview');
    });

    // delete Media
    $(document).on('click', '.delete-category-media', function (){
        var currEle = $(this);
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var url = currEle.data('href');
                $.ajax({
                    url: url,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            currEle.next('.display-thumbnail').attr('src', defaultImage);
                            currEle.remove();
                            $('#edit_upload_image').val('');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $(document).on('submit', 'form#category_add_form', function(e) {
        e.preventDefault();
        $(this)
            .find('button[type="submit"]')
            .attr('disabled', true);
        var data = new FormData($(this)[0]);

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            success: function(result) {
                if (result.success === true) {
                    $('div.category_modal').modal('hide');
                    toastr.success(result.msg);
                    category_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', 'button.edit_category_button', function() {
        $('div.category_modal').load($(this).data('href'), function() {
            $(this).modal('show');

            $('form#category_edit_form').submit(function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = new FormData($(this)[0]);

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(result) {
                        if (result.success === true) {
                            $('div.category_modal').modal('hide');
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });

    $(document).on('click', 'button.assign_unassign_locations_button', function() {

        $('div.addLocationsModal').load($(this).data('href'), function() {
            $(this).modal('show');
            //$('#addModifiersModal').modal('show');


            $('form#assign_unassign_form').submit(function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = new FormData($(this)[0]);

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(result) {
                        if (result.success === true) {

                            $('div.addLocationsModal').modal('hide');
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });

    $(document).on('click', 'button.assign_unassign_modifiers_button', function() {

        $('div.addCategoryModifiersModal').load($(this).data('href'), function() {
            $(this).modal('show');
            //$('#addModifiersModal').modal('show');


            $('form#assign_unassign_form').submit(function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = new FormData($(this)[0]);

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(result) {
                        if (result.success === true) {

                            $('div.addCategoryModifiersModal').modal('hide');
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });

    $(document).on('click', 'button.delete_category_button', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
</script>
