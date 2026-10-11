@extends('layouts.app')
@section('title', __('restaurant.modifier_option'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'restaurant.modifier_option' )
        <small>@lang( 'restaurant.manage_your_modifiers_recipe' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">

    <div class="box">
        <div class="box-header">
            <h3 class="box-title">@lang( 'restaurant.all_your_modifiers_recipe' )</h3>
        </div>
        <div class="box-body">
            @can('restaurant.view')
            <table class="table table-bordered table-striped" id="modifier_table">
                <thead>
                    <tr>
                        <th>@lang( 'restaurant.modifier_sets' )</th>
                        <th>@lang( 'restaurant.modifiers' )</th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                </thead>
            </table>
            @endcan
        </div>
    </div>
    <div class="modal fade addModifiersIngredintModal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<!-- /.content -->
@endsection

@section('javascript')

<script>
$(document).ready(function() {


    //Brands table
    var modifier_table = $('#modifier_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/modules/modifiers-option',
        columnDefs: [{
            "targets": [1],
            "orderable": false,
            "searchable": false
        }],
        columns: [
            {
                data: 'modifier_set_name',
                name: 'p.sku'
            },
            {
                data: 'name',
                name: 'variations.name'
            },

            {
                data: 'action',
                name: 'action'
            }
        ],
    });
    $(document).on('click', 'button.delete_recipe', function() {
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
                        if (result.success == true) {
                            toastr.success(result.msg);
                            modifier_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $(document).on('click', 'button.edit_modifier_option_button', function() {
        $("div.addModifiersIngredintModal").load($(this).data('href'), function() {
            $(this).modal('show');
            $('form#edit_form').submit(function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                $.ajax({
                    method: "POST",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.addModifiersIngredintModal').modal(
                                'hide');
                            toastr.success(result.msg);
                            modifier_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
        });
    });
    // Recipe Management
    $('.ingredient-row-sortable').sortable({
        cursor: "move",
        handle: ".handle",
        update: function(event, ui) {
            $(this).children().each(function(index) {
                $(this).find('input.sort_order').val(++index)
            });
        }
    });

    $('body').ready(function() {
        $(document).on('input', '#search_product', function() {
            console.log($(this).val());
            initialize_search($(this));
        });
    });

    // Count Product Unit Price Tax
    function toFixedTrunc(x, n) {
        const abc = typeof x === 'string' ? x : x.toString();
        const v = abc.split('.');
        if (n <= 0) return v[0];
        let f = v[1] || '';
        if (f.length > n) return `${v[0]}.${f.substr(0, n)}`;
        while (f.length < n) f += '0';
        return `${v[0]}.${f}`;
    }
    $(document).on('click', '#save_recipe', function(e) {
        var ingredients_length = $('.ingredients_table tbody .quantity').length;
        if (ingredients_length < 1) {
            toastr.error('@lang("manufacturing::lang.please_add_ingredients")');
            e.preventDefault();
            return false;
        } else {
            var variation_id = $('#variation_id').val();
            var ingredients_cost = $('#ingredients_cost').val();
            var ingredients = [];
            var obj = {};
            $("input[name^='ingredients").each(function(index, el) {
                let arrayKey = $(this).prop("name").toLowerCase();
                let arrayKeyEnd = arrayKey.replace("ingredients[", "");
                let arrayKeyNew = arrayKeyEnd.replace(/.$/, "]");

                var keyValue = $(this).val();
                obj[arrayKeyNew] = keyValue;

            });
            $.ajax({
                type: 'post',
                url: '/manufacturing/store-product-ingredient',
                data: {
                    variation_id: variation_id,
                    ingredients_cost: ingredients_cost,
                    ingredients: obj,

                },
                dataType: 'json',
                success: function(result) {
                    if (result.success == true) {
                        $('div.addModifiersIngredintModal').modal('hide');
                        modifier_table.ajax.reload();
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        }
    });


    $(document).on('change', '.quantity,.row_sub_unit_id, #sub_unit_id', function() {
        calculateRecipeTotal();
    });

    function initialize_search(element) {
        element.autocomplete({
            source: function(request, response) {
                $.getJSON(
                    '/products/list-material', {
                        term: request.term,
                        not_for_selling: 1,
                        product_types: ['single', 'variable']
                    },
                    response
                );
            },
            minLength: 2,
            response: function(event, ui) {
                if (ui.content.length == 0) {
                    toastr.error(LANG.no_products_found);
                    $('input#search_product').select();
                }
            },
            select: function(event, ui) {
                addIngredientRow(ui.item.variation_id, $(this));
            },
        }).autocomplete('instance')._renderItem = function(ul, item) {

            var string = '<li>' + item.name;
            if (item.type == 'variable') {
                string += '-' + item.variation;
            }
            string +=
                ' (' +
                item.sub_sku +
                ')' +
                '</li>';
            return $(string).appendTo(ul);
        }
    }
    $(document).on('click', 'button.remove_ingredient', function() {
        element = $(this).closest('tbody.ingredient-row-sortable');
        $(this).closest('tr').remove();
        //set the order of ingredient
        $(element).children().each(function(index) {
            $(this).find('input.sort_order').val(++index)
        });
        calculateRecipeTotal();
    });


    function addIngredientRow(variation_id, search_element) {
        var row_index = parseInt($('#row_index').val());
        var ingredient_group = search_element.closest('.box').find('.ingredient_group');
        var row_ig_index = ingredient_group.length ? ingredient_group.data('ig_index') : '';
        var sort_order = ++(search_element.closest('.box').find('.ingredient-row-sortable').children().length);

        $.ajax({
            url: "/manufacturing/get-ingredient-row-product/" + variation_id + '?row_index=' +
                row_index +
                '&row_ig_index=' + row_ig_index + '&sort_order=' + sort_order,
            dataType: 'html',
            success: function(result) {
                search_element.closest('.box').find('table.ingredients_table tbody').append(result);
                calculateRecipeTotal();
                row_index++;
                $('#row_index').val(row_index);
            },
        });
    }

    function calculateRecipeTotal() {
        var total = 0;
        $('.ingredients_table tbody tr').each(function() {
            var line_unit_price = $(this).find('.ingredient_price').val();
            var quantity = __read_number($(this).find('.quantity'));
            var multiplier = 1;
            if ($(this).find('.row_sub_unit_id').length) {
                multiplier = parseFloat(
                    $(this).find('.row_sub_unit_id')
                    .find(':selected')
                    .data('multiplier')
                );
            }

            var line_total = line_unit_price * quantity * multiplier;
            $(this).find('span.ingredient_price').text(line_total);
            total += line_total;
        });
        $('span#ingredients_cost_text').text(total);
        $('#ingredients_cost').val(total);
    }
});
</script>
@endsection
