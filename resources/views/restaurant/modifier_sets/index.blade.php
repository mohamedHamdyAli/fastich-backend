@extends('layouts.app')
@section('title', __('restaurant.modifiers'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'restaurant.modifier_sets' )
        <small>@lang( 'restaurant.manage_your_modifiers' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">

    <div class="box">
        <div class="box-header">
            <div class="col-xs-12 col-sm-12">
            <h3 class="box-title">@lang( 'restaurant.all_your_modifiers' )</h3>
            </div>
            <div class="col-lg-offset-7 col-lg-2 col-xs-12 col-sm-12 col-md-12">
                @can('restaurant.create')
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary"  href="{{ action('Restaurant\ModifierSetsController@getModifiers') }}">
                            <i class="fa fa-plus"></i> @lang( 'restaurant.modifier_option' )</a>
                    </div>
                @endcan
            </div>
            <div class="col-lg-offset-2 col-lg-1 col-sm-12 col-xs-12 col-md-12">
                @can('restaurant.create')
                    <div class="box-tools">
                        <button type="button" class="btn btn-block btn-primary btn-modal add-product-modifier-modal"
                            data-href="{{action('Restaurant\ModifierSetsController@create')}}" data-container=".modifier_modal">
                            <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                    </div>
                @endcan
            </div>

        </div>
        <div class="box-body">
            @can('restaurant.view')
            <table class="table table-bordered table-striped" id="modifier_table">
                <thead>
                    <tr>
                        <th>@lang( 'restaurant.modifier_sets' )</th>
                        <th>@lang( 'restaurant.modifiers' )</th>
                        <th>@lang( 'restaurant.products' )</th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                </thead>
            </table>
            @endcan
        </div>
    </div>

    <div class="modal fade modifier_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')

<script>
let taxRates = {!! isset($taxRates) ? json_encode($taxRates) : 'undefined' !!};
$(document).ready(function() {

    $(document).on('click', 'button.remove-modifier-row', function(e) {
        $(this).closest('tr').remove();
    });

    $(document).on('submit', 'form#table_add_form', function(e) {
        e.preventDefault();
        var data = $(this).serialize();

        $.ajax({
            method: "POST",
            url: $(this).attr("action"),
            dataType: "json",
            data: data,
            success: function(result) {
                if (result.success == true) {
                    $('div.modifier_modal').modal('hide');
                    toastr.success(result.msg);
                    modifier_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    //Brands table
    var modifier_table = $('#modifier_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/modules/modifiers',
        columnDefs: [{
            "targets": [1, 2, 3],
            "orderable": false,
            "searchable": false
        }],
        columns: [{
                data: 'name',
                name: 'name'
            },
            {
                data: 'variations',
                name: 'variations'
            },
            {
                data: 'modifier_products',
                name: 'modifier_products'
            },
            {
                data: 'action',
                name: 'action'
            }
        ],
    });

    $(document).on('click', 'button.edit_modifier_button', function() {

        $("div.modifier_modal").load($(this).data('href'), function() {

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
                            $('div.modifier_modal').modal('hide');
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

    $(document).on('click', 'button.delete_modifier_button', function() {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_table,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: "DELETE",
                    url: href,
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            modifier_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });

    // Add Modal
    $(document).on('click', 'button.add-modifier-row', function() {
        // $('table#add-modifier-table').append($(this).data('html'));
        $('table#add-modifier-table tbody').append(`
                    <tr>
                        <td>
                          <div class="form-group">
                            <input type="text" name="modifier_name[]"
                            class="form-control"
                            placeholder="Name" required>
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                            <input type="text" name="modifier_price[]" class="form-control input_number modifier-price"
                            placeholder="Price" required>
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                              <select name="modifier_tax[]" class="form-control select2 modifier-tax-select2 modifier-tax">
                                <option value="">Please Select</option>
                              </select>
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                            <select name="modifier_tax_type[]" class="form-control select2 modifier-tax-type">
                                <option value="exclusive">Exclusive</option>
                                <option value="inclusive">Inclusive</option>
                            </select>
                          </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <input type="text" name="modifier_price_inc_tax[]" readonly class="form-control input_number modifier-price-inc-tax" required />
                            </div>
                        </td>
                        <td>
                            <button class='btn btn-primary btn-xs pull-right add-modifier-row' type='button'>
                                <i class='fa fa-plus'></i>
                            </button>
                        </td>
                        <td>
                            <button class="btn btn-danger btn-xs pull-right remove-modifier-row" type="button"><i class="fa fa-minus"></i></button>
                        </td>
                    </tr>
                        `);
        $.each(taxRates, function(i, v) {
            $('table#add-modifier-table tbody tr:last').
            find('td div select.modifier-tax-select2').
            append($(`<option data-amount="${v.amount}" value="${v.id}">${v.name}</option>`));
        });
        $('.select2').each(function() {
            $(this).select2({
                width: '100%',
            });
        });
    });



    // Modifier Tax DropDown
    $(document).on('change', '.modifier-tax', function() {
        let modifierPriceIncTaxRounded;
        let modifierTaxEle = $(this);
        let currentTrTag = modifierTaxEle.parent().parent().parent();
        let modifierTax = $('option:selected', modifierTaxEle);
        let modifierTaxAmount = modifierTax.attr('data-amount');
        let modifierPrice = currentTrTag.find('input.modifier-price');
        let modifierTaxType = currentTrTag.find('select.modifier-tax-type option:selected');
        let modifierTaxTypeValue = modifierTaxType.val();
        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-inc-tax');
        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPrice) {
            modifierPriceIncTax.removeAttr('readonly');

            let priceValue = toFixedTrunc(modifierPrice.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            modifierPriceIncTaxRounded = parseFloat(priceValue) + calculateRate;

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPrice.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    // Modifier Tax Type DropDown
    $(document).on('change', '.modifier-tax-type', function() {
        let modifierPriceIncTaxRounded;
        let modifierTaxTypeEle = $(this);
        let currentTrTag = modifierTaxTypeEle.parent().parent().parent();
        let modifierTaxTypeValue = modifierTaxTypeEle.val();

        let modifierPrice = currentTrTag.find('input.modifier-price');

        let modifierTax = currentTrTag.find('select.modifier-tax option:selected');
        let modifierTaxAmount = modifierTax.attr('data-amount');

        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-inc-tax');

        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPrice) {
            modifierPriceIncTax.removeAttr('readonly');

            let priceValue = toFixedTrunc(modifierPrice.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            modifierPriceIncTaxRounded = parseFloat(priceValue) + calculateRate;

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPrice.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    // Edit Modal
    $(document).on('click', 'button.add-modifier-row-edit', function() {
        $('table#add-modifier-table tbody').append(`
                    <tr>
                        <td>
                          <div class="form-group">
                            <input type="text" name="modifier_name[]"
                            class="form-control"
                            placeholder="Name" required>
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                            <input type="text" name="modifier_price[]" class="form-control input_number modifier-price-edit"
                            placeholder="Price" required>
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                              <select name="modifier_tax[]" class="form-control select2 modifier-tax-select2-edit modifier-tax-edit">
                                <option value="">Please Select</option>
                              </select>
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                            <select name="modifier_tax_type[]" class="form-control select2 modifier-tax-type-edit">
                                <option value="exclusive">Exclusive</option>
                                <option value="inclusive">Inclusive</option>
                            </select>
                          </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <input type="text" name="modifier_price_inc_tax[]" readonly class="form-control input_number modifier-price-inc-tax-edit" required />
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-danger btn-xs pull-right remove-modifier-row" type="button"><i class="fa fa-minus"></i></button>
                        </td>
                    </tr>
                        `);
        $.each(taxRates, function(i, v) {
            $('table#add-modifier-table tbody tr:last').
            find('td div select.modifier-tax-select2-edit').
            append($(`<option data-amount="${v.amount}" value="${v.id}">${v.name}</option>`));
        });
        $('.select2').each(function() {
            $(this).select2({
                width: '100%',
            });
        });
    });

    // Modifier Price
    $(document).on('keyup', '.modifier-price', function() {
        let modifierPriceIncTaxRounded;
        let modifierPriceEle = $(this);
        let currentTrTag = modifierPriceEle.parent().parent().parent();
        let modifierTax = currentTrTag.find('select.modifier-tax option:selected');
        let modifierTaxAmount = modifierTax.attr('data-amount');
        let tax_percent_multiply = (((parseFloat(modifierTaxAmount) + 100) / 100));
        let modifierTaxType = currentTrTag.find('select.modifier-tax-type option:selected');
        let modifierTaxTypeValue = modifierTaxType.val();
        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-inc-tax');
        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPriceEle) {
            modifierPriceIncTax.removeAttr('readonly');
            let priceValue = toFixedTrunc(modifierPriceEle.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            //modifierPriceIncTaxRounded = parseFloat(priceValue) + calculateRate;
            modifierPriceIncTaxRounded = (parseFloat(priceValue) * tax_percent_multiply);

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPriceEle.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    // Modifier Price Edit
    $(document).on('keyup', '.modifier-price-edit', function() {
        let modifierPriceIncTaxRounded;
        let modifierPriceEle = $(this);
        let currentTrTag = modifierPriceEle.parent().parent().parent();
        let modifierTax = currentTrTag.find('select.modifier-tax-edit option:selected');
        let modifierTaxAmount = modifierTax.attr('data-amount');
        let tax_percent_multiply = (((parseFloat(modifierTaxAmount) + 100) / 100));
        let modifierTaxType = currentTrTag.find('select.modifier-tax-type-edit option:selected');
        let modifierTaxTypeValue = modifierTaxType.val();
        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-inc-tax-edit');
        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPriceEle) {
            modifierPriceIncTax.removeAttr('readonly');
            let priceValue = toFixedTrunc(modifierPriceEle.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            //modifierPriceIncTaxRounded = (parseFloat(priceValue) + calculateRate);
            modifierPriceIncTaxRounded = (parseFloat(priceValue) * tax_percent_multiply);

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPriceEle.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });
    // Modifier Price Edit
    $(document).on('keyup', '.modifier-price-inc-tax-edit', function() {
        let modifierPriceIncTaxRounded;
        let modifierPriceEle = $(this);
        let currentTrTag = modifierPriceEle.parent().parent().parent();
        let modifierTax = currentTrTag.find('select.modifier-tax-edit option:selected');
        let modifierTaxAmount = modifierTax.attr('data-amount');
        let tax_percent = (((parseFloat(modifierTaxAmount) + 100) / 100));
        let modifierTaxType = currentTrTag.find('select.modifier-tax-type-edit option:selected');
        let modifierTaxTypeValue = modifierTaxType.val();
        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-edit');
        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPriceEle) {
            modifierPriceIncTax.removeAttr('readonly');
            let priceValue = toFixedTrunc(modifierPriceEle.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            //modifierPriceIncTaxRounded = (parseFloat(priceValue) + calculateRate);
            modifierPriceIncTaxRounded = (parseFloat(priceValue) / tax_percent);

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPriceEle.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    // Modifier Price Edit
    $(document).on('keyup', '.modifier-price-inc-tax', function() {
        let modifierPriceIncTaxRounded;
        let modifierPriceEle = $(this);
        let currentTrTag = modifierPriceEle.parent().parent().parent();
        let modifierTax = currentTrTag.find('select.modifier-tax option:selected');
        let modifierTaxAmount = modifierTax.attr('data-amount');
        let tax_percent = (((parseFloat(modifierTaxAmount) + 100) / 100));
        let modifierTaxType = currentTrTag.find('select.modifier-tax-type option:selected');
        let modifierTaxTypeValue = modifierTaxType.val();
        let modifierPriceIncTax = currentTrTag.find('input.modifier-price');
        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPriceEle) {
            modifierPriceIncTax.removeAttr('readonly');
            let priceValue = toFixedTrunc(modifierPriceEle.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            //modifierPriceIncTaxRounded = (parseFloat(priceValue) + calculateRate);
            modifierPriceIncTaxRounded = (parseFloat(priceValue) / tax_percent);

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPriceEle.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    // Modifier Tax DropDown Edit
    $(document).on('change', '.modifier-tax-edit', function() {
        let modifierPriceIncTaxRounded;
        let modifierTaxEle = $(this);
        let currentTrTag = modifierTaxEle.parent().parent().parent();
        let modifierTax = $('option:selected', modifierTaxEle);
        let modifierTaxAmount = modifierTax.attr('data-amount');
        let modifierPrice = currentTrTag.find('input.modifier-price-edit');
        let modifierTaxType = currentTrTag.find('select.modifier-tax-type-edit option:selected');
        let modifierTaxTypeValue = modifierTaxType.val();
        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-inc-tax-edit');
        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPrice) {
            modifierPriceIncTax.removeAttr('readonly');

            let priceValue = toFixedTrunc(modifierPrice.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            modifierPriceIncTaxRounded = parseFloat(priceValue) + calculateRate;

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPrice.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    // Modifier Tax Type DropDown Edit
    $(document).on('change', '.modifier-tax-type-edit', function() {
        let modifierPriceIncTaxRounded;
        let modifierTaxTypeEle = $(this);
        let currentTrTag = modifierTaxTypeEle.parent().parent().parent();
        let modifierTaxTypeValue = modifierTaxTypeEle.val();

        let modifierPrice = currentTrTag.find('input.modifier-price-edit');

        let modifierTax = currentTrTag.find('select.modifier-tax-edit option:selected');
        let modifierTaxAmount = modifierTax.attr('data-amount');

        let modifierPriceIncTax = currentTrTag.find('input.modifier-price-inc-tax-edit');

        if (modifierTaxAmount && modifierTaxTypeValue === 'inclusive' && modifierPrice) {
            modifierPriceIncTax.removeAttr('readonly');

            let priceValue = toFixedTrunc(modifierPrice.val(), 2);
            let rate = priceValue * modifierTaxAmount / 100;
            let calculateRate = (Math.ceil(rate * 100) / 100);
            modifierPriceIncTaxRounded = parseFloat(priceValue) + calculateRate;

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        } else {
            modifierPriceIncTax.attr('readonly', 'readonly');

            modifierPriceIncTaxRounded = parseFloat(toFixedTrunc(modifierPrice.val(), 2));

            isNaN(modifierPriceIncTaxRounded) ? modifierPriceIncTax.val(0) : modifierPriceIncTax.val(
                modifierPriceIncTaxRounded.toFixed(2));
        }
    });

    $(document).on('click', '.add-product-modifier-modal', function() {
        setTimeout(function() {
            let modal = $(document).find('.product-modifier-modal');
            modal.find('.select2').each(function() {
                $(this).select2({
                    width: '100%',
                });
            });
        }, 1000);
    });

    $(document).on('click', '.edit-product-modifier-modal', function() {
        setTimeout(function() {
            let modal = $(document).find('.product-modifier-modal');
            modal.find('.select2').each(function() {
                $(this).select2({
                    width: '100%',
                });
            });
        }, 1000);
    });

    $(document).on('click', 'button.remove_modifier_product', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $(this).closest('tr').remove();
            }
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
});
</script>
@endsection
