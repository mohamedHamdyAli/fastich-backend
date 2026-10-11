<?php
namespace App\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;
use Menu;

class AdminSidebarMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->ajax()) {
            return $next($request);
        }

        Menu::create('admin-sidebar-menu', function ($menu) {
            $common_settings = ! empty(session()->get('business.common_settings')) ? session()->get('business.common_settings') : [];
            $enable_brand    = (session()->get('business.enable_brand') == 1) ? session()->get('business.enable_brand') : 0;
            //$enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];
            $enabled_modules = ! empty(session('enabled_modules')) ? session('enabled_modules') : [];

            $business_id        = request()->session()->get('user.business_id');
            $kitchen_station_id = request()->session()->get('user.kitchen_station_id');
            /**
             * dump(request()->session()->get('enabled_modules'));exit; *
             */
            $showKitchen = true;
            if (($kitchen_station_id == '')) {
                $showKitchen = false;
            }

            if ($business_id == '7' || $business_id == '2') {
            }
            $enabledMenuLink = [];

            //Home
            $menu->url(action('HomeController@index'), __('home.home'), ['icon' => 'fa fas fa-tachometer-alt', 'active' => request()->segment(1) == 'home'])->order(5);

            //User management dropdown
            if ((auth()->user()->can('user.view') || auth()->user()->can('user.create') || auth()->user()->can('roles.view') || auth()->user()->can('roles.create')) && in_array('user_module', $enabled_modules)) {
                $menu->dropdown(
                    __('user.user_management'),
                    function ($sub) use ($enabledMenuLink) {
                        /*  users */
                        if (auth()->user()->can('user.view')) {
                            $sub->url(
                                action('ManageUserController@index'),
                                __('user.users'),
                                ['icon' => 'fa fas fa-user', 'active' => request()->segment(1) == 'users']
                            );
                        }
                        /*  roles */
                        if (auth()->user()->can('roles.view')) {
                            $sub->url(
                                action('RoleController@index'),
                                __('user.roles'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(1) == 'roles']
                            );
                        }
                        /*  sales_commission_agents */
                        if (in_array('sales_commission_agents', $enabledMenuLink) && auth()->user()->can('user.create')) {
                            $sub->url(
                                action('SalesCommissionAgentController@index'),
                                __('lang_v1.sales_commission_agents'),
                                ['icon' => 'fa fas fa-handshake', 'active' => request()->segment(1) == 'sales-commission-agents']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-users']
                )->order(10);
            }

            //Contacts dropdown
            /* ! */
            if ((auth()->user()->can('supplier.view') || auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own')) &&
                (in_array('customer_module', $enabled_modules) || (in_array('supplier_module', $enabled_modules)))
            ) {
                $menu->dropdown(
                    __('contact.contacts'),
                    function ($sub) use ($enabledMenuLink, $enabled_modules) {
                        $sub->dropdown(
                            __('report.customer'),
                            function ($sub) use ($enabled_modules, $enabledMenuLink) {
                                /*  list_customer  */
                                if ((auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own')) && in_array('customer_module', $enabled_modules)) {
                                    $sub->url(
                                        action('ContactController@index', ['type' => 'customer']),
                                        __('report.list_customer'),
                                        ['icon' => 'fa fas fa-star', 'active' => request()->input('type') == 'customer']
                                    );
                                }
                                /*  customer_groups */
                                if (! in_array('customer_groups', $enabledMenuLink) && auth()->user()->can('customer.view') && in_array('loyalty_module', $enabled_modules)) {
                                    $sub->url(
                                        action('CustomerGroupController@index'),
                                        __('lang_v1.customer_groups'),
                                        ['icon' => 'fa fas fa-users', 'active' => request()->segment(1) == 'customer-group']
                                    );
                                }
                                /*  import_contacts */
                                if (
                                    in_array('import_contacts', $enabledMenuLink) && (auth()->user()->can('supplier.create') || auth()->user()->can('customer.create'))
                                    && in_array('direct_sales_module', $enabled_modules)
                                ) {
                                    $sub->url(
                                        action('ContactController@getImportContacts'),
                                        __('lang_v1.import_contacts'),
                                        ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'import']
                                    );
                                }
                            }
                        );
                        /*  list_supplier */
                        if (auth()->user()->can('supplier.view') && in_array('supplier_module', $enabled_modules)) {
                            $sub->url(
                                action('ContactController@index', ['type' => 'supplier']),
                                __('report.list_supplier'),
                                ['icon' => 'fa fas fa-star', 'active' => request()->input('type') == 'supplier']
                            );
                        }
                        /*  map */
                        if (in_array('map', $enabledMenuLink) && ! empty(env('GOOGLE_MAP_API_KEY')) && in_array('direct_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('ContactController@contactMap'),
                                __('lang_v1.map'),
                                ['icon' => 'fa fas fa-map-marker-alt', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'map']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-address-book', 'id' => "tour_step4"]
                )->order(15);
            } /* */

            //Products dropdown
            if ((in_array('product_module', $enabled_modules) || in_array('inventory_module', $enabled_modules)) &&
                (auth()->user()->can('product.view') || auth()->user()->can('product.create') ||
                    auth()->user()->can('brand.view') || auth()->user()->can('unit.view') ||
                    auth()->user()->can('category.view') || auth()->user()->can('brand.create') ||
                    auth()->user()->can('unit.create') || auth()->user()->can('category.create'))
            ) {
                $menu->dropdown(
                    __('sale.products'),
                    function ($sub) use ($enabled_modules, $common_settings, $enable_brand, $enabledMenuLink) {
                        /*  list_products */
                        if (auth()->user()->can('product.view') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('ProductController@index'),
                                __('lang_v1.list_products'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'products' && request()->segment(2) == '']
                            );
                        }
                        /*  add_product */
                        if (auth()->user()->can('product.create') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('ProductController@create'),
                                __('product.add_product'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'products' && request()->segment(2) == 'create']
                            );
                        }
                        /*  print_labels */
                        if (! in_array('print_labels', $enabledMenuLink) && auth()->user()->can('product.view') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('LabelsController@show'),
                                __('barcode.print_labels'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'labels' && request()->segment(2) == 'show']
                            );
                        }
                        /*  import_products */
                        if (! in_array('import_products', $enabledMenuLink) && auth()->user()->can('product.create') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('ImportProductsController@index'),
                                __('product.import_products'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-products']
                            );
                        }
                        /*  units */
                        if ((auth()->user()->can('unit.view') || auth()->user()->can('unit.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('UnitController@index'),
                                __('unit.units'),
                                ['icon' => 'fa fas fa-balance-scale', 'active' => request()->segment(1) == 'units']
                            );
                        }
                        /*  categories */
                        if ((auth()->user()->can('category.view') || auth()->user()->can('category.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('TaxonomyController@index') . '?type=product',
                                __('category.categories'),
                                ['icon' => 'fa fas fa-tags', 'active' => request()->segment(1) == 'taxonomies' && request()->get('type') == 'product']
                            );
                        }
                        /*  brands */
                        if ($enable_brand == 1 && (auth()->user()->can('brand.view') || auth()->user()->can('brand.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('BrandController@index'),
                                __('brand.brands'),
                                ['icon' => 'fa fas fa-gem', 'active' => request()->segment(1) == 'brands']
                            );
                        }
                        /*  warranties */
                        if (array_key_exists('enable_product_warranty', $common_settings) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('WarrantyController@index'),
                                __('lang_v1.warranties'),
                                ['icon' => 'fa fas fa-shield-alt', 'active' => request()->segment(1) == 'warranties']
                            );
                        }
                        /*  modifiers */
                        if (in_array('product_module', $enabled_modules) && (auth()->user()->can('product.view') || auth()->user()->can('product.create'))) {
                            $sub->url(
                                action('Restaurant\ModifierSetsController@index'),
                                __('restaurant.modifiers'),
                                ['icon' => 'fa fas fa-pizza-slice', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'modifiers']
                            );
                        }
                        /*  modifier_option */
                        if (in_array('product_module', $enabled_modules) && in_array('inventory_module', $enabled_modules) && (auth()->user()->can('product.view') || auth()->user()->can('product.create'))) {
                            $sub->url(
                                '/modules/modifiers-option',
                                __('restaurant.modifier_option'),
                                ['icon' => 'fa fas fa-pizza-slice', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'modifiers-option']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-cubes', 'id' => 'tour_step5']
                )->order(20);
            }

            /*  Purchase dropdown */
            if (in_array('purchasing_module', $enabled_modules) && (auth()->user()->can('purchase.view')
                || auth()->user()->can('purchase.create') || auth()->user()->can('purchase.update'))) {
                $menu->dropdown(
                    __('purchase.purchases'),
                    function ($sub) use ($enabled_modules) {
                        /*  list_purchase */
                        if ((auth()->user()->can('purchase.view') || auth()->user()->can('view_own_purchase')) && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('PurchaseController@index'),
                                __('purchase.list_purchase'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == null]
                            );
                        }
                        /*  add_purchase */
                        if (auth()->user()->can('purchase.create') && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('PurchaseController@create'),
                                __('purchase.add_purchase'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == 'create']
                            );
                        }
                        /*  list_purchase_return */
                        if (auth()->user()->can('purchase.update') && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('PurchaseReturnController@index'),
                                __('lang_v1.list_purchase_return'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'purchase-return']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-arrow-circle-down', 'id' => 'tour_step6']
                )->order(25);
            }
            /*  Sell dropdown */
            if ((in_array('direct_sales_module', $enabled_modules) || in_array('point_of_sales_module', $enabled_modules) || in_array('barcode_pos', $enabled_modules)) &&
                (auth()->user()->can('sell.view') || auth()->user()->can('sell.add') || auth()->user()->can('sell.return') || auth()->user()->can('sell.bpos.view') || auth()->user()->can('sell.pos.view') || auth()->user()->can('sell.view_all'))
            ) {
                $menu->dropdown(
                    __('sale.sale'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {
                        /*  all_sales */
                        if ((auth()->user()->can('sell.view') || auth()->user()->can('sell.view_all') || auth()->user()->can('sell.bpos.view') || auth()->user()->can('sell.pos.view')
                            || auth()->user()->can('sell.pos.view_branch_sell') || auth()->user()->can('sell.bpos.view_branch_sell'))) {
                            $sub->url(
                                action('SellController@index'),
                                __('lang_v1.all_sales'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == null]
                            );
                        }
                        /*  add_sale */
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.add')) {
                            $sub->url(
                                action('SellController@create'),
                                __('sale.add_sale'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'create' && empty(request()->get('status'))]
                            );
                        }
                        /*  new_pos_sale */
                        if (auth()->user()->can('sell.pos.add') && in_array('point_of_sales_module', $enabled_modules)) {
                            $sub->url(
                                '/pos/new',
                                __('sale.new_pos_sale'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == 'new']
                            );
                        }
                        /*  list_drafts */
                        if (auth()->user()->can('list_drafts') && in_array('direct_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('SellController@getDrafts'),
                                __('lang_v1.list_drafts'),
                                ['icon' => 'fa fas fa-pen-square', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'drafts']
                            );
                        }
                        /*  add_quotation */
                        if (in_array('direct_sales_module', $enabled_modules) && (auth()->user()->can('sell.add') || auth()->user()->can('sell.add_quotation'))) {
                            $sub->url(
                                action('SellController@create', ['status' => 'quotation']),
                                __('lang_v1.add_quotation'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->get('status') == 'quotation']
                            );
                        }
                        /*  list_quotations */
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.list_quotations')) {
                            $sub->url(
                                action('SellController@getQuotations'),
                                __('lang_v1.list_quotations'),
                                ['icon' => 'fa fas fa-pen-square', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'quotations']
                            );
                        }
                        /*  list_sell_return */
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.return')) {
                            $sub->url(
                                action('SellReturnController@index'),
                                __('lang_v1.list_sell_return'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'sell-return' && request()->segment(2) == null]
                            );
                        }
                        /*  shipments */
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.access_shipping')) {
                            $sub->url(
                                action('SellController@shipments'),
                                __('lang_v1.shipments'),
                                ['icon' => 'fa fas fa-truck', 'active' => request()->segment(1) == 'shipments']
                            );
                        }
                        /*  subscriptions */
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.add_edit_subscription')) {
                            $sub->url(
                                action('SellPosController@listSubscriptions'),
                                __('lang_v1.subscriptions'),
                                ['icon' => 'fa fas fa-recycle', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'subscriptions' && empty(request()->get('status'))]
                            );
                        }
                        /*  import_sales */
                        if (in_array('import_sales', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.import_sales')) {
                            $sub->url(
                                action('ImportSalesController@index'),
                                __('lang_v1.import_sales'),
                                ['icon' => 'fa fas fa-file-import', 'active' => request()->segment(1) == 'import-sales']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-arrow-circle-up', 'id' => 'tour_step7']
                )->order(30);
            }
            /* Expense dropdown */
            if (in_array('expenses_module', $enabled_modules) && (auth()->user()->can('expense.access') || auth()->user()->can('view_own_expense'))) {
                $menu->dropdown(
                    __('expense.expenses'),
                    function ($sub) use ($enabled_modules) {
                        /* list_expenses */
                        if (in_array('expenses_module', $enabled_modules) && auth()->user()->can('expense.view')) {
                            $sub->url(
                                action('ExpenseController@index'),
                                __('lang_v1.list_expenses'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == null]
                            );
                        }
                        /* add_expenses */
                        if (in_array('expenses_module', $enabled_modules) && auth()->user()->can('expense.add')) {
                            $sub->url(
                                action('ExpenseController@create'),
                                __('expense.add_expense'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == 'create']
                            );
                        }
                        /* expense_categories */
                        if (in_array('expenses_module', $enabled_modules) && auth()->user()->can('expense.category.add')) {
                            $sub->url(
                                action('ExpenseCategoryController@index'),
                                __('expense.expense_categories'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'expense-categories']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-minus-circle']
                )->order(35);
            }

            /*  Inventory dropdown  */
            if (in_array('inventory_module', $enabled_modules) && (auth()->user()->can('stock.transfer.view') || auth()->user()->can('stock.adjustment.view'))) {
                $menu->dropdown(
                    __('lang_v1.stock'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {
                        /* import_opening_stock */
                        if (! in_array('import_opening_stock', $enabledMenuLink) && auth()->user()->can('stock.import_opening_stock')) {
                            $sub->url(
                                action('ImportOpeningStockController@index'),
                                __('lang_v1.import_opening_stock'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-opening-stock']
                            );
                        }
                        /* stock_transfers */
                        if (auth()->user()->can('stock.transfer.view') || auth()->user()->can('stock.transfer.add')) {
                            $sub->dropdown(
                                __('lang_v1.stock_transfers'),
                                function ($sub) use ($enabled_modules) {
                                    /* list_stock_transfers */
                                    if (auth()->user()->can('stock.transfer.view')) {
                                        $sub->url(
                                            action('StockTransferController@index'),
                                            __('lang_v1.list_stock_transfers'),
                                            ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == null]
                                        );
                                    }
                                    /* add_stock_transfer */
                                    if (auth()->user()->can('stock.transfer.add')) {
                                        $sub->url(
                                            action('StockTransferController@create'),
                                            __('lang_v1.add_stock_transfer'),
                                            ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == 'create']
                                        );
                                    }
                                }
                            );
                        }
                        /* stock_adjustments */
                        if (auth()->user()->can('stock.adjustment.view') || auth()->user()->can('stock.adjustment.add')) {
                            $sub->dropdown(
                                __('lang_v1.stock_adjustments'),
                                function ($sub) use ($enabled_modules) {
                                    /* list_stock_adjustment */
                                    if (auth()->user()->can('stock.adjustment.view')) {
                                        $sub->url(
                                            action('StockAdjustmentController@index'),
                                            __('lang_v1.list_stock_adjustment'),
                                            ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == null]
                                        );
                                    }
                                    /* add_stock_adjustment */
                                    if (auth()->user()->can('stock.adjustment.add')) {
                                        $sub->url(
                                            action('StockAdjustmentController@create'),
                                            __('lang_v1.add_stock_adjustment'),
                                            ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == 'create']
                                        );
                                    }
                                }
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-truck']
                )->order(40);
            }
            /*  marketing dropdown */
            if (auth()->user()->can('discount.view') && in_array('offer_discount_module', $enabled_modules)) {
                $menu->dropdown(
                    __('lang_v1.offer_discount'),
                    function ($sub) {
                        /*  list_discounts */
                        if (auth()->user()->can('discount.view')) {
                            $sub->url(
                                action('DiscountController@index'),
                                __('lang_v1.list_discounts'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(1) == 'discount']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-gift']
                )->order(45);
            }
            /*  Tables dropdown */
            if (auth()->user()->can('access_tables') && in_array('table_module', $enabled_modules)) {
                $menu->dropdown(
                    __('lang_v1.tables'),
                    function ($sub) use ($enabledMenuLink) {
                        /*  list_tables */
                        if (auth()->user()->can('access_tables')) {
                            $sub->url(
                                action('Restaurant\TableController@index'),
                                __('lang_v1.list_tables'),
                                ['icon' => 'fa fas fa-table', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'tables']
                            );
                        }
                        /*  bookings */
                        if (! in_array('bookings', $enabledMenuLink) && (auth()->user()->can('crud_all_bookings') || auth()->user()->can('crud_own_bookings'))) {
                            $sub->url(
                                action('Restaurant\BookingController@index'),
                                __('restaurant.bookings'),
                                ['icon' => 'fas fa fa-calendar-check', 'active' => request()->segment(1) == 'bookings']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-table']
                )->order(50);
            }
            /*  Kitchen dropdown */
            if (! $showKitchen && in_array('kitchen_module', $enabled_modules) && auth()->user()->can('access_kitchen_screen')) {
                $menu->dropdown(
                    __('lang_v1.kitchen'),
                    function ($sub) use ($enabled_modules) {
                        /*  list_kitchen */
                        if (auth()->user()->can('access_kitchen_screen')) {
                            $sub->url(
                                action('Restaurant\KitchenController@index'),
                                __('lang_v1.list_kitchen'),
                                ['icon' => 'fa fas fa-fire', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'kitchen']
                            );
                        }
                        /*  orders */
                        if (auth()->user()->can('access_kitchen_order') && in_array('service_staff_module', $enabled_modules)) {
                            $sub->url(
                                action('Restaurant\OrderController@index'),
                                __('restaurant.orders'),
                                ['icon' => 'fa fas fa-list-alt', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'orders']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-snowflake']
                )->order(55);
            }

            //Reports dropdown
            if ((in_array('basic_reports_module', $enabled_modules) || in_array('advance_reports_module', $enabled_modules) || in_array('payment_account_module', $enabled_modules)) &&
                (
                    auth()->user()->can('purchase_n_sell_report.view')
                    || auth()->user()->can('contacts_report.view')
                    || auth()->user()->can('stock_report.view')
                    || auth()->user()->can('tax_report.view')
                    || auth()->user()->can('trending_product_report.view')
                    || auth()->user()->can('sales_representative.view')
                    || auth()->user()->can('register_report.view')
                    || auth()->user()->can('expense_report.view')
                    || auth()->user()->can('report.sell_payment.view')
                    || auth()->user()->can('report.sell.view')
                    || auth()->user()->can('report.product_sell.view')
                    || auth()->user()->can('report.product_purchase.view')
                    || auth()->user()->can('report.item.view')
                    || auth()->user()->can('report.service_staff.view')
                    || auth()->user()->can('report.sell_by_branch.view')
                    || auth()->user()->can('report.purchase_payment.view')
                    || auth()->user()->can('report.table.view')

                )
            ) {
                $menu->dropdown(
                    __('report.reports'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {
                        /* sales */
                        if (in_array('advance_reports_module', $enabled_modules)) {
                            $sub->dropdown(
                                __('sale.sale'),
                                function ($sub) use ($enabled_modules) {
                                    /* sell_payment_report */
                                    if (
                                        auth()->user()->can('report.sell_payment.view')
                                        && in_array('advance_reports_module', $enabled_modules)
                                    ) {
                                        $sub->url(
                                            action('ReportController@sellPaymentReport'),
                                            __('lang_v1.sell_payment_report'),
                                            ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'sell-payment-report']
                                        );
                                    }
                                    /* sales_report_by_branch */
                                    if (auth()->user()->can('report.sell_by_branch.view') && in_array('advance_reports_module', $enabled_modules)) {
                                        $sub->url(
                                            action('ReportController@getSalesByBranchReport'),
                                            __('report.sales_report_by_branch'),
                                            ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'sales-by-branch-report']
                                        );
                                    }
                                }
                            );
                        }
                        /* purchases */
                        if (in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules)) {
                            $sub->dropdown(
                                __('purchase.purchases'),
                                function ($sub) use ($enabled_modules,$enabledMenuLink) {
                                    /* purchase_payment_report */
                                    if (auth()->user()->can('report.purchase_payment.view') && in_array('purchase_payment', $enabledMenuLink)
                                        && in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules)
                                    ) {
                                        $sub->url(
                                            action('ReportController@purchasePaymentReport'),
                                            __('lang_v1.purchase_payment_report'),
                                            ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'purchase-payment-report']
                                        );
                                    }
                                    /* purchase_sell_report */
                                    if ((in_array('advance_reports_module', $enabled_modules))
                                        && (in_array('purchasing_module', $enabled_modules)) && auth()->user()->can('purchase_n_sell_report.view')
                                    ) {
                                        $sub->url(
                                            action('ReportController@getPurchaseSell'),
                                            __('report.purchase_sell_report'),
                                            ['icon' => 'fa fas fa-exchange-alt', 'active' => request()->segment(2) == 'purchase-sell']
                                        );
                                    }
                                }
                            );
                        }
                        /* products */
                        if ((in_array('advance_reports_module', $enabled_modules) || in_array('basic_reports_module', $enabled_modules)) && in_array('product_module', $enabled_modules)) {
                            $sub->dropdown(
                                __('lang_v1.products'),
                                function ($sub) use ($enabled_modules, $enabledMenuLink) {
                                    /* product_sell_report_branch_by_branch */
                                    if (
                                        in_array('basic_reports_module', $enabled_modules) && in_array('product_module', $enabled_modules)
                                        && auth()->user()->can('report.product_sell.view')
                                    ) {
                                        $sub->url(
                                            action('ReportController@getProductSellBranchByBranchReport'),
                                            __('report.product_sell_report_branch_by_branch'),
                                            ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'product-sell-branch-by-branch-report']
                                        );
                                    }
                                    /* trending_products */
                                    if ( in_array('trending_product_report', $enabledMenuLink) && 
                                        in_array('advance_reports_module', $enabled_modules) && in_array('product_module', $enabled_modules)
                                        && auth()->user()->can('trending_product_report.view')
                                    ) {
                                        $sub->url(
                                            action('ReportController@getTrendingProducts'),
                                            __('report.trending_products'),
                                            ['icon' => 'fa fas fa-chart-line', 'active' => request()->segment(2) == 'trending-products']
                                        );
                                    }
                                    /* items_report */
                                    if (
                                        in_array('advance_reports_module', $enabled_modules) && in_array('items_report', $enabledMenuLink)
                                        && in_array('product_module', $enabled_modules)
                                        && auth()->user()->can('report.item.view')
                                    ) {
                                        $sub->url(
                                            action('ReportController@itemsReport'),
                                            __('lang_v1.items_report'),
                                            ['icon' => 'fa fas fa-tasks', 'active' => request()->segment(2) == 'items-report']
                                        );
                                    }
                                    /* product_purchase_report */
                                    if (
                                        in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules)
                                        && auth()->user()->can('report.product_purchase.view')
                                    ) {
                                        $sub->url(
                                            action('ReportController@getproductPurchaseReport'),
                                            __('lang_v1.product_purchase_report'),
                                            ['icon' => 'fa fas fa-arrow-circle-down', 'active' => request()->segment(2) == 'product-purchase-report']
                                        );
                                    }
                                }
                            );
                        }
                        /* Inventory */
                        if (in_array('advance_reports_module', $enabled_modules) && auth()->user()->can('stock_report.view') && in_array('inventory_module', $enabled_modules)) {
                            $sub->dropdown(
                                __('lang_v1.stock'),
                                function ($sub) use ($enabled_modules) {
                                    /* stock_report */
                                    if (
                                        in_array('inventory_module', $enabled_modules)
                                        && in_array('advance_reports_module', $enabled_modules)
                                    ) {
                                        $sub->url(
                                            action('ReportController@getStockReport'),
                                            __('report.stock_report'),
                                            ['icon' => 'fa fas fa-hourglass-half', 'active' => request()->segment(2) == 'stock-report']
                                        );
                                    }
                                    /* stock_expiry_report */
                                    if (
                                        in_array('inventory_module', $enabled_modules)
                                        && in_array('advance_reports_module', $enabled_modules) && session('business.enable_product_expiry') == 1
                                    ) {
                                        $sub->url(
                                            action('ReportController@getStockExpiryReport'),
                                            __('report.stock_expiry_report'),
                                            ['icon' => 'fa fas fa-calendar-times', 'active' => request()->segment(2) == 'stock-expiry']
                                        );
                                    }
                                    /* stock_adjustment_report */
                                    if (
                                        in_array('advance_reports_module', $enabled_modules)
                                        && in_array('inventory_module', $enabled_modules)
                                    ) {
                                        $sub->url(
                                            action('ReportController@getStockAdjustmentReport'),
                                            __('report.stock_adjustment_report'),
                                            ['icon' => 'fa fas fa-sliders-h', 'active' => request()->segment(2) == 'stock-adjustment-report']
                                        );
                                    }
                                }
                            );
                        }
                        /* Customer */
                        if (in_array('advance_reports_module', $enabled_modules) && in_array('customer_module', $enabled_modules)) {
                            $sub->dropdown(
                                __('report.customer'),
                                function ($sub) use ($enabled_modules) {
                                    /* list_customer */
                                    if (
                                        auth()->user()->can('contacts_report.view')
                                        && (in_array('customer_module', $enabled_modules) || in_array('supplier_module', $enabled_modules))
                                        && in_array('advance_reports_module', $enabled_modules)
                                    ) {
                                        $sub->url(
                                            action('ReportController@getCustomerSuppliers'),
                                            __('report.customer'),
                                            ['icon' => 'fa fas fa-address-book', 'active' => request()->segment(2) == 'customer-supplier']
                                        );
                                    }
                                    /* customer_groups_report */
                                    if (
                                        auth()->user()->can('contacts_report.view')
                                        && (in_array('customer_module', $enabled_modules) || in_array('supplier_module', $enabled_modules))
                                        && in_array('advance_reports_module', $enabled_modules)
                                    ) {
                                        $sub->url(
                                            action('ReportController@getCustomerGroup'),
                                            __('lang_v1.customer_groups_report'),
                                            ['icon' => 'fa fas fa-users', 'active' => request()->segment(2) == 'customer-group']
                                        );
                                    }
                                }
                            );
                        }
                        /* supplier report */
                        if (
                            in_array('advance_reports_module', $enabled_modules) && auth()->user()->can('report.supplier.view')
                            && (in_array('supplier_module', $enabled_modules))
                        ) {
                            $sub->url(
                                action('ReportController@getCustomerSuppliers'),
                                __('report.supplier'),
                                ['icon' => 'fa fas fa-address-book', 'active' => request()->segment(2) == 'customer-supplier']
                            );
                        }
                        /* expense_report */
                        if (
                            in_array('advance_reports_module', $enabled_modules)
                            && in_array('expenses_module', $enabled_modules) && auth()->user()->can('expense_report.view')
                        ) {
                            $sub->url(
                                action('ReportController@getExpenseReport'),
                                __('report.expense_report'),
                                ['icon' => 'fa fas fa-search-minus', 'active' => request()->segment(2) == 'expense-report']
                            );
                        }
                        /* profit_loss */
                        if (
                            in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules) && auth()->user()->can('profit_loss_report.view')
                            && in_array('expenses_module', $enabled_modules)
                        ) {
                            $sub->url(
                                action('ReportController@getProfitLoss'),
                                __('report.profit_loss'),
                                ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'profit-loss']
                            );
                        }
                        /* tax_report */
                        if (in_array('basic_reports_module', $enabled_modules) && auth()->user()->can('tax_report.view')) {
                            $sub->url(
                                action('ReportController@getTaxReport'),
                                __('report.tax_report'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(2) == 'tax-report']
                            );
                        }
                        /* table_report */
                        if (
                            in_array('table_module', $enabled_modules) && in_array('basic_reports_module', $enabled_modules)
                            && auth()->user()->can('report.table.view')
                        ) {
                            $sub->url(
                                action('ReportController@getTableReport'),
                                __('restaurant.table_report'),
                                ['icon' => 'fa fas fa-table', 'active' => request()->segment(2) == 'table-report']
                            );
                        }
                        /* service_staff_report */
                        if (in_array('basic_reports_module', $enabled_modules) && auth()->user()->can('report.service_staff.view') && in_array('service_staff_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getServiceStaffReport'),
                                __('restaurant.service_staff_report'),
                                ['icon' => 'fa fas fa-user-secret', 'active' => request()->segment(2) == 'service-staff-report']
                            );
                        }
                        /* sales_representative */
                        if (
                            in_array('basic_reports_module', $enabled_modules) && in_array('user_module', $enabled_modules)
                            && auth()->user()->can('sales_representative.view')
                        ) {
                            $sub->url(
                                action('ReportController@getSalesRepresentativeReport'),
                                __('report.sales_representative'),
                                ['icon' => 'fa fas fa-user', 'active' => request()->segment(2) == 'sales-representative-report']
                            );
                        }
                        /* payment-account-report */
                        if (in_array('advance_reports_module', $enabled_modules) && in_array('payment_account_module', $enabled_modules)) {
                            $sub->url(
                                action('AccountReportsController@paymentAccountReport'),
                                __('account.payment_account_report'),
                                ['icon' => 'fa fas fa-file-alt', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'payment-account-report']
                            );
                        }
                        /* register_report */
                        if (in_array('basic_reports_module', $enabled_modules) && auth()->user()->can('register_report.view')) {
                            $sub->url(
                                action('ReportController@getRegisterReport'),
                                __('report.register_report'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(2) == 'register-report']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-chart-bar', 'id' => 'tour_step8']
                )->order(60);
            }

            /*  payment Accounts dropdown */
            if (! in_array('payment_accounts', $enabledMenuLink) && auth()->user()->can('account.access') && in_array('payment_account_module', $enabled_modules)) {
                $menu->dropdown(
                    __('lang_v1.payment_accounts'),
                    function ($sub) {
                        $sub->url(
                            action('AccountController@index'),
                            __('account.list_accounts'),
                            ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'account']
                        );
                        $sub->url(
                            action('AccountReportsController@balanceSheet'),
                            __('account.balance_sheet'),
                            ['icon' => 'fa fas fa-book', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'balance-sheet']
                        );
                        $sub->url(
                            action('AccountReportsController@trialBalance'),
                            __('account.trial_balance'),
                            ['icon' => 'fa fas fa-balance-scale', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'trial-balance']
                        );
                        $sub->url(
                            action('AccountController@cashFlow'),
                            __('lang_v1.cash_flow'),
                            ['icon' => 'fa fas fa-exchange-alt', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'cash-flow']
                        );
                    },
                    ['icon' => 'fa fas fa-money-check-alt']
                )->order(65);
            }
            /*  Notification template menu */
            if ((in_array('user_module', $enabled_modules) || in_array('direct_sales_module', $enabled_modules) || in_array('product_module', $enabled_modules) || in_array('point_of_sales_module', $enabled_modules))
                && ! in_array('notification_templates', $enabledMenuLink) && auth()->user()->can('send_notifications')
            ) {
                $menu->url(
                    action('NotificationTemplateController@index'),
                    __('lang_v1.notification_templates'),
                    ['icon' => 'fa fas fa-envelope', 'active' => request()->segment(1) == 'notification-templates']
                )->order(70);
            }

            /*  Integration  menu dropdown */
            if (auth()->user()->can('business_settings.access')) {
                $menu->dropdown(
                    __('lang_v1.integrations'),
                    function ($sub) use ($enabled_modules) {
                        /* deliverect integration */
                        if (auth()->user()->can('business_settings.access') && in_array('delivery_aggregators', $enabled_modules)) {
                            $sub->url(
                                action('DeliverectIntegrationController@index'),
                                __('lang_v1.deliverect_integration'),
                                ['icon' => 'fa fas fa-bolt', 'active' => request()->segment(1) == 'deliverect-integration']
                            );
                        }
                        /* Zatca integration */
                        if (auth()->user()->can('business_settings.access')) {
                            $sub->url(
                                action('ZatcaController@index'),
                                __('lang_v1.zatca_integration'),
                                ['icon' => 'fa fas fa-bolt', 'active' => request()->segment(1) == 'zatca-integration']
                            );
                        }

                    },
                    ['icon' => 'fa fas fa-universal-access']
                )->order(75);
            }

            /*  Settings Dropdown */
            if ((
                auth()->user()->can('business_settings.access') ||
                auth()->user()->can('barcode_settings.access') ||
                auth()->user()->can('invoice_settings.access') ||
                auth()->user()->can('tax_rate.view') ||
                auth()->user()->can('tax_rate.create') ||
                (auth()->user()->can('superadmin.access_package_subscriptions') && auth()->user()->can('business_settings.access'))
            )) {
                $menu->dropdown(
                    __('business.settings'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {

                        /* business settings */
                        if (auth()->user()->can('business_settings.access')) {
                            $sub->url(
                                action('BusinessController@getBusinessSettings'),
                                __('business.business_settings'),
                                ['icon' => 'fa fas fa-cogs', 'active' => request()->segment(1) == 'business', 'id' => "tour_step2"]
                            );
                        }
                        /* business location */
                        if (auth()->user()->can('business_settings.access') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('BusinessLocationController@index'),
                                __('business.business_locations'),
                                ['icon' => 'fa fas fa-map-marker', 'active' => request()->segment(1) == 'business-location']
                            );
                        }
                        /* invoice settings */
                        if (auth()->user()->can('invoice_settings.access') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('InvoiceSchemeController@index'),
                                __('invoice.invoice_settings'),
                                ['icon' => 'fa fas fa-file', 'active' => in_array(request()->segment(1), ['invoice-schemes', 'invoice-layouts'])]
                            );
                        }

                        /* barcode_settings */
                        if (! in_array('barcode_settings', $enabledMenuLink) && auth()->user()->can('barcode_settings.access') && in_array('direct_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('BarcodeController@index'),
                                __('barcode.barcode_settings'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'barcodes']
                            );
                        }

                        /* receipt printers */
                        if (in_array('receipt_printers', $enabledMenuLink) && in_array('product_module', $enabled_modules) && auth()->user()->can('access_printers')) {
                            $sub->url(
                                action('PrinterController@index'),
                                __('printer.receipt_printers'),
                                ['icon' => 'fa fas fa-share-alt', 'active' => request()->segment(1) == 'printers']
                            );
                        }
                        /* tax rate */
                        if ((auth()->user()->can('tax_rate.view') || auth()->user()->can('tax_rate.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('TaxRateController@index'),
                                __('tax_rate.tax_rates'),
                                ['icon' => 'fa fas fa-bolt', 'active' => request()->segment(1) == 'tax-rates']
                            );
                        }
                        /* client app */
                        if (auth()->user()->can('business_settings.access') && in_array('point_of_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('ClientAppController@index'),
                                __('business.clientApp'),
                                ['icon' => 'fa fas fa-shipping-fast', 'active' => request()->segment(1) == 'client-app']
                            );
                        }
                        /* types of service */
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('access_types_of_service')) {
                            $sub->url(
                                action('TypesOfServiceController@index'),
                                __('lang_v1.types_of_service'),
                                ['icon' => 'fa fas fa-user-circle', 'active' => request()->segment(1) == 'types-of-service']
                            );
                        }

                    },
                    ['icon' => 'fa fas fa-cog', 'id' => 'tour_step3']
                )->order(75);
            }

            /*   Backup menu */
            if (in_array('backup', $enabledMenuLink) && auth()->user()->can('backup')) {
                $menu->url(
                    action('BackUpController@index'),
                    __('lang_v1.backup'),
                    ['icon' => 'fa fas fa-hdd', 'active' => request()->segment(1) == 'backup']
                )->order(85);
            }

            /*  Modules menu only superadmin */
            if (in_array('modules', $enabledMenuLink) && auth()->user()->can('manage_modules')) {
                $menu->url(
                    action('Install\ModulesController@index'),
                    __('lang_v1.modules'),
                    ['icon' => 'fa fas fa-plug', 'active' => request()->segment(1) == 'manage-modules']
                )->order(90);
            }
        });

        //Add menus from modules
        $moduleUtil = new ModuleUtil;
        $moduleUtil->getModuleData('modifyAdminMenu');
        return $next($request);
    }
}
