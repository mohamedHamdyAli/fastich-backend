@extends('layouts.app')
@section('title', __('role.add_role'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('role.add_role')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    {!! Form::open(['url' => action('RoleController@store'), 'method' => 'post', 'id' => 'role_add_form']) !!}
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('name', __('user.role_name') . ':*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('user.role_name')]) !!}
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <h2>@lang('user.permissions'):</h2>
        </div>
    </div>
    <hr>
    {{-- user Type --}}
    @if (in_array('service_staff_module', $enabled_modules))
    <div class="row">
        <div class="col-md-2">
            <h4>@lang('lang_v1.user_type')</h4>
        </div>
        <div class="col-md-9 col-md-offset-1">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_service_staff', 1, false, ['class' => 'input-icheck']) !!} {{ __('restaurant.service_staff') }}
                    </label>
                    @show_tooltip(__('restaurant.tooltip_service_staff'))
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- user --}}
    @if (in_array('user_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.user')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'user.view', false, ['class' => 'input-icheck']) !!} {{ __('role.user.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'user.create', false, ['class' => 'input-icheck']) !!} {{ __('role.user.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'user.update', false, ['class' => 'input-icheck']) !!} {{ __('role.user.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'user.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.user.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Roles --}}
    @if (in_array('user_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('user.roles')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'roles.view', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_role') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'roles.create', false, ['class' => 'input-icheck']) !!} {{ __('role.add_role') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'roles.update', false, ['class' => 'input-icheck']) !!} {{ __('role.edit_role') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'roles.delete', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.delete_role') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Suppliers --}}
    @if (in_array('supplier_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.supplier')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'supplier.view', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_all_supplier') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'supplier.view_own', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_own_supplier') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'supplier.create', false, ['class' => 'input-icheck']) !!} {{ __('role.supplier.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'supplier.update', false, ['class' => 'input-icheck']) !!} {{ __('role.supplier.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'supplier.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.supplier.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Customer --}}
    @if (in_array('customer_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.customer')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'customer.view', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_all_customer') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'customer.view_own', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_own_customer') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'customer.create', false, ['class' => 'input-icheck']) !!} {{ __('role.customer.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'customer.update', false, ['class' => 'input-icheck']) !!} {{ __('role.customer.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'customer.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.customer.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Products --}}
    @if (in_array('product_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('business.product')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.view', false, ['class' => 'input-icheck']) !!} {{ __('role.product.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.create', false, ['class' => 'input-icheck']) !!} {{ __('role.product.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.update', false, ['class' => 'input-icheck']) !!} {{ __('role.product.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.product.delete') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.add_recipe', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.add_recipe') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.import_products', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.import_products') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'product.opening_stock', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.add_opening_stock') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'view_purchase_price', false, ['class' => 'input-icheck']) !!}
                        {{ __('lang_v1.view_purchase_price') }}
                    </label>
                    @show_tooltip(__('lang_v1.view_purchase_price_tooltip'))
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Unit --}}
    @if (in_array('product_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.unit')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'unit.view', false, ['class' => 'input-icheck']) !!} {{ __('role.unit.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'unit.create', false, ['class' => 'input-icheck']) !!} {{ __('role.unit.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'unit.update', false, ['class' => 'input-icheck']) !!} {{ __('role.unit.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'unit.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.unit.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Category --}}
    @if (in_array('product_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('category.category')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'category.view', false, ['class' => 'input-icheck']) !!} {{ __('role.category.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'category.create', false, ['class' => 'input-icheck']) !!} {{ __('role.category.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'category.update', false, ['class' => 'input-icheck']) !!} {{ __('role.category.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'category.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.category.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Modifiers --}}
    @if (in_array('product_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('lang_v1.modifiers')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'modifiers.view', false, ['class' => 'input-icheck']) !!} {{ __('role.modifier.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'modifiers.create', false, ['class' => 'input-icheck']) !!} {{ __('role.modifier.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'modifiers.update', false, ['class' => 'input-icheck']) !!} {{ __('role.modifier.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'modifiers.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.modifier.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Purchases --}}
    @if (in_array('purchasing_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.purchase')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.view', false, ['class' => 'input-icheck']) !!} {{ __('role.purchase.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.create', false, ['class' => 'input-icheck']) !!} {{ __('role.purchase.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.update', false, ['class' => 'input-icheck']) !!} {{ __('role.purchase.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.purchase.delete') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.return', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.purchase.return') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.update_status', false, ['class' => 'input-icheck']) !!}
                        {{ __('lang_v1.update_status') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'view_own_purchase', false, ['class' => 'input-icheck']) !!}
                        {{ __('lang_v1.view_own_purchase') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.payments.add', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.purchase.payments.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.payments.edit', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.purchase.payments.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase.payments.delete', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.purchase.payments.delete') }}
                    </label>
                </div>
            </div>

        </div>
    </div>
    <hr>
    @endif

    {{-- Stock --}}
    @if (in_array('inventory_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.stock')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.add_edit_opening_stock', false, ['class' => 'input-icheck']) !!} {{ __('role.stock.add_edit_opening_stock') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.import_opening_stock', false, ['class' => 'input-icheck']) !!} {{ __('role.stock.import_opening_stock') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.adjustment.view', false, ['class' => 'input-icheck']) !!} {{ __('role.stock.adjustment.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.adjustment.add', false, ['class' => 'input-icheck']) !!} {{ __('role.stock.adjustment.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.adjustment.edit', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.stock.adjustment.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.adjustment.delete', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.stock.adjustment.delete') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.adjustment.view_own_only', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.stock.adjustment.view_own_only') }}
                    </label>
                </div>
            </div>

            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.transfer.view', false, ['class' => 'input-icheck']) !!} {{ __('role.stock.transfer.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.transfer.add', false, ['class' => 'input-icheck']) !!} {{ __('role.stock.transfer.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.transfer.edit', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.stock.transfer.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.transfer.delete', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.stock.transfer.delete') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock.transfer.view_own_only', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.stock.transfer.view_own_only') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- POS --}}
    @if (in_array('point_of_sales_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.pos_sale')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.view', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.view_branch_sell', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.view_branch_sell') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.add', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.edit', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.edit_branch_sell', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.edit_branch_sell') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.return', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.return') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.edit_product_price_from_pos_screen', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.edit_product_price_from_pos_screen') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.add_product_discount_from_pos_screen', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.pos.add_product_discount_from_pos_screen') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.customer.add', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.pos.customer.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.view_cash_register', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.pos.view_cash_register') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.pos.close_cash_register', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.pos.close_cash_register') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Barcode POS --}}
    @if (in_array('point_of_sales_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.bpos_sale')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.view', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.view_branch_sell', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.view_branch_sell') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.add', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.edit', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.edit_branch_sell', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.edit_branch_sell') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.return', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.return') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.edit_product_price_from_pos_screen', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.edit_product_price_from_pos_screen') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.add_product_discount_from_pos_screen', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.bpos.add_product_discount_from_pos_screen') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.customer.add', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.bpos.customer.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.view_cash_register', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.bpos.view_cash_register') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.bpos.close_cash_register', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.bpos.close_cash_register') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif


    {{-- Sales --}}
    @if (in_array('direct_sales_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.direct_sale')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.view', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.add', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.edit', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.return', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.return') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.add_quotation', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.add_quotation') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.list_quotations', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.list_quotations') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.list_drafts', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.list_drafts') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.access_types_of_service', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.access_types_of_service') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.access_shipping', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.access_shipping') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.payments', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.payments') }}
                    </label>
                   {{--  @show_tooltip(__('lang_v1.sell_payments')) --}}
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.add_edit_subscription', false, ['class' => 'input-icheck']) !!}
                        {{ __('role.sell.add_edit_subscription') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.delete_subscription', false, [
                        'class' => 'input-icheck',
                        ]) !!}
                        {{ __('role.sell.delete_subscription') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif

    {{-- Sale manager --}}
    @if (in_array('direct_sales_module', $enabled_modules) || in_array('point_of_sales_module', $enabled_modules) || in_array('barcode_pos', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.sale_manager')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.view_all', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.view_all') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sell.import_sales', false, ['class' => 'input-icheck']) !!} {{ __('role.sell.import_sales') }}
                    </label>
                </div>
            </div> 
        </div>
    </div>
    <hr>
    @endif

    {{-- Discount --}}
    @if (in_array('offer_discount_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.discounts')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'discount.view', false, ['class' => 'input-icheck']) !!} {{ __('role.discount.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'discount.add', false, ['class' => 'input-icheck']) !!} {{ __('role.discount.add') }}
                    </label>
                </div>
            </div> 
             <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'discount.edit', false, ['class' => 'input-icheck']) !!} {{ __('role.discount.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'discount.activate_deactivate', false, ['class' => 'input-icheck']) !!} {{ __('role.discount.activate_deactivate') }}
                    </label>
                </div>
            </div> 
        </div>
    </div>
    <hr>
    @endif

     {{-- Expense --}}
    @if (in_array('expenses_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.expenses')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.view', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.add', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.add') }}
                    </label>
                </div>
            </div> 
             <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.edit', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.edit') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.delete') }}
                    </label>
                </div>
            </div> 
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.payment.add', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.payment.add') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.payment.view', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.payment.view') }}
                    </label>
                </div>
            </div> 
             <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.view_own_expense', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.view_own_expense') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.category.add', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.category.add') }}
                    </label>
                </div>
            </div> 
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.category.edit', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.category.edit') }}
                    </label>
                </div>
            </div> 
        </div>
    </div>
    <hr>
    @endif

    {{-- cash register --}}
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('cash_register.cash_register')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'view_cash_register', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_cash_register') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'close_cash_register', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.close_cash_register') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>

 {{-- cash register --}}
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.brand')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'brand.view', false, ['class' => 'input-icheck']) !!} {{ __('role.brand.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'brand.create', false, ['class' => 'input-icheck']) !!} {{ __('role.brand.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'brand.update', false, ['class' => 'input-icheck']) !!} {{ __('role.brand.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'brand.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.brand.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>

     {{-- cash register --}}
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.tax_rate')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'tax_rate.view', false, ['class' => 'input-icheck']) !!} {{ __('role.tax_rate.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'tax_rate.create', false, ['class' => 'input-icheck']) !!} {{ __('role.tax_rate.create') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'tax_rate.update', false, ['class' => 'input-icheck']) !!} {{ __('role.tax_rate.update') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'tax_rate.delete', false, ['class' => 'input-icheck']) !!} {{ __('role.tax_rate.delete') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
 {{-- cash register --}}
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.report')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            @if (in_array('purchasing_module', $enabled_modules) ||
            in_array('direct_sales_module', $enabled_modules) ||
            in_array('point_of_sales_module', $enabled_modules))
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'purchase_n_sell_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.purchase_n_sell_report.view') }}
                    </label>
                </div>
            </div>
            @endif
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'tax_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.tax_report.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'contacts_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.contacts_report.view') }}
                    </label>
                </div>
            </div>
            @if (in_array('expenses_module', $enabled_modules))
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.expense_report.view') }}
                    </label>
                </div>
            </div>
            @endif
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'profit_loss_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.profit_loss_report.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'stock_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.stock_report.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'trending_product_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.trending_product_report.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'register_report.view', false, ['class' => 'input-icheck']) !!} {{ __('role.register_report.view') }}
                    </label>
                </div>
            </div>

            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'sales_representative.view', false, ['class' => 'input-icheck']) !!} {{ __('role.sales_representative.view') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'view_product_stock_value', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.view_product_stock_value') }}
                    </label>
                </div>
            </div>

        </div>
    </div>
    <hr>
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('role.settings')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'business_settings.access', false, ['class' => 'input-icheck']) !!} {{ __('role.business_settings.access') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'barcode_settings.access', false, ['class' => 'input-icheck']) !!} {{ __('role.barcode_settings.access') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'invoice_settings.access', false, ['class' => 'input-icheck']) !!} {{ __('role.invoice_settings.access') }}
                    </label>
                </div>
            </div>
            @if (in_array('expenses_module', $enabled_modules))
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'expense.access', false, ['class' => 'input-icheck']) !!} {{ __('role.expense.access') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'view_own_expense', false, ['class' => 'input-icheck']) !!}
                        {{ __('lang_v1.view_own_expense') }}
                    </label>
                </div>
            </div>
            @endif
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'access_printers', false, ['class' => 'input-icheck']) !!}
                        {{ __('lang_v1.access_printers') }}
                    </label>
                </div>
            </div>

        </div>
    </div>
    <hr>
     {{-- cash register --}}
    <div class="row check_group">
        <div class="col-md-3">
            <h4>@lang('role.dashboard') @show_tooltip(__('tooltip.dashboard_permission'))</h4>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'dashboard.data', true, ['class' => 'input-icheck']) !!} {{ __('role.dashboard.data') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
     {{-- cash register --}}
    <div class="row check_group">
        <div class="col-md-3">
            <h4>@lang('account.account')</h4>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'account.access', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.access_accounts') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
     {{-- Table  --}}
    @if (in_array('table_module', $enabled_modules))
    <div class="row check_group">
        <div class="col-md-1">
            <h4>@lang('restaurant.bookings')</h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                </label>
            </div>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'crud_all_bookings', false, ['class' => 'input-icheck']) !!} {{ __('restaurant.add_edit_view_all_booking') }}
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'crud_own_bookings', false, ['class' => 'input-icheck']) !!} {{ __('restaurant.add_edit_view_own_booking') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    <hr>
    @endif
    <div class="row">
        <div class="col-md-3">
            <h4>@lang('lang_v1.access_selling_price_groups')</h4>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'access_default_selling_price', true, ['class' => 'input-icheck']) !!} {{ __('lang_v1.default_selling_price') }}
                    </label>
                </div>
            </div>
            @if (count($selling_price_groups) > 0)
            @foreach ($selling_price_groups as $selling_price_group)
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('spg_permissions[]', 'selling_price_group.' . $selling_price_group->id, false, [
                        'class' => 'input-icheck',
                        ]) !!} {{ $selling_price_group->name }}
                    </label>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>

     {{-- cash register --}}
    @if (in_array('table_module', $enabled_modules))
    <div class="row">
        <div class="col-md-3">
            <h4>@lang('restaurant.restaurant')</h4>
        </div>
        <div class="col-md-9">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', 'access_tables', false, ['class' => 'input-icheck']) !!} {{ __('lang_v1.access_tables') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('role.partials.module_permissions')
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
        </div>
    </div>

    {!! Form::close() !!}
    @endcomponent
</section>
<!-- /.content -->
@endsection

