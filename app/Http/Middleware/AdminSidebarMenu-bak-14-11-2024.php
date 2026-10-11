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
            $common_settings = !empty(session()->get('business.common_settings')) ? session()->get('business.common_settings') : [];
            /*  $enabledMenuLink = !empty(session()->get('business.enabled_menu_link')) ? session()->get('business.enabled_menu_link') : []; */
            $enable_brand = (session()->get('business.enable_brand') == 1) ? session()->get('business.enable_brand') : 0;
            //$enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];
            $enabled_modules = !empty(session('enabled_modules')) ? session('enabled_modules') : [];

            $business_id = request()->session()->get('user.business_id');
            $kitchen_station_id = request()->session()->get('user.kitchen_station_id');
            /* dump(request()->session()->get('enabled_modules'));exit; */
            $showKitchen = true;
            if (($kitchen_station_id == '')) {
                $showKitchen = false;
            }

            if ($business_id == '7' || $business_id == '2') {
                $enabledMenuLink = [
                    "list_pos",
                    "pos_sale",
                    /* "modules", */
                ];

            } else {
                $enabledMenuLink = [
                    /* "supplier", */
                    "print_labels",
                    "variations",
                    "stock_adjustment",
                    "stock_transfers",
                    "import_contacts",
                    "import_sales",
                    "list_sell_return",
                    "shipments",
                    "list_quotations",
                    "pos_sale",
                    "list_pos",
                    "add_quotation",
                    "payment_accounts",
                    "backup",
                    "notification_templates",
                    "bookings",
                    "orders",
                    "map",
                    "modules",
                    "sales_representative",
                    "expense_report",
                    "items_report",
                    "product_purchase_report",
                    "purchase_payment_report",
                    "product_sell_report",
                    "trending_products",
                    "service_staff_report",
                    "table_report",
                    "purchase_n_sell_report",
                    "stock_adjustment_report",
                    "lot_report",
                    "stock_expiry_report",
                    "stock_report",
                    "getCustomerSuppliers",
                    "purchase_sell_report",
                    "profit_loss",
                    "types_of_service",
                    "barcode_settings",
                    "subscriptions",
                ];

            }
            //dump($enabledMenuLink);exit;

            //Home
            $menu->url(action('HomeController@index'), __('home.home'), ['icon' => 'fa fas fa-tachometer-alt', 'active' => request()->segment(1) == 'home'])->order(5);

            //User management dropdown
            if ((auth()->user()->can('user.view') || auth()->user()->can('user.create') || auth()->user()->can('roles.view') || auth()->user()->can('roles.create')) && in_array('user_module', $enabled_modules)) {
                $menu->dropdown(
                    __('user.user_management'),
                    function ($sub) use ($enabledMenuLink) {
                        if (auth()->user()->can('user.view')) {
                            $sub->url(
                                action('ManageUserController@index'),
                                __('user.users'),
                                ['icon' => 'fa fas fa-user', 'active' => request()->segment(1) == 'users']
                            );
                        }
                        /* ! */
                        if (auth()->user()->can('roles.view')) {
                            $sub->url(
                                action('RoleController@index'),
                                __('user.roles'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(1) == 'roles']
                            );
                        }
                        /* ! */
                        if (in_array('sales_commission_agents', $enabledMenuLink) && auth()->user()->can('user.create')) {
                            $sub->url(
                                action('SalesCommissionAgentController@index'),
                                __('lang_v1.sales_commission_agents'),
                                ['icon' => 'fa fas fa-handshake', 'active' => request()->segment(1) == 'sales-commission-agents']
                            );
                        } /* */
                    },
                    ['icon' => 'fa fas fa-users']
                )->order(10);
            }

            //Contacts dropdown
            /* ! */
            if ((auth()->user()->can('supplier.view') || auth()->user()->can('customer.view')) && (in_array('customer_module', $enabled_modules)) ||
                (in_array('supplier_module', $enabled_modules)) || (in_array('direct_sales_module', $enabled_modules)) || (in_array('loyalty_module', $enabled_modules))) {
                $menu->dropdown(__('contact.contacts'),
                    function ($sub) use ($enabledMenuLink, $enabled_modules) {
                        $sub->dropdown(__('report.customer'),
                            function ($sub) use ($enabled_modules, $enabledMenuLink) {
                                if (auth()->user()->can('customer.view') && in_array('customer_module', $enabled_modules)) {
                                    $sub->url(
                                        action('ContactController@index', ['type' => 'customer']), __('report.list_customer'),
                                        ['icon' => 'fa fas fa-star', 'active' => request()->input('type') == 'customer']
                                    );
                                }
                                if (!in_array('customer_groups', $enabledMenuLink) && auth()->user()->can('customer.view') && in_array('loyalty_module', $enabled_modules)) {
                                    $sub->url(
                                        action('CustomerGroupController@index'),
                                        __('lang_v1.customer_groups'),
                                        ['icon' => 'fa fas fa-users', 'active' => request()->segment(1) == 'customer-group']
                                    );
                                }
                                if (!in_array('import_contacts', $enabledMenuLink) && (auth()->user()->can('supplier.create') || auth()->user()->can('customer.create')) && in_array('direct_sales_module', $enabled_modules)) {
                                    $sub->url(
                                        action('ContactController@getImportContacts'),
                                        __('lang_v1.import_contacts'),
                                        ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'import']
                                    );
                                }

                            }
                        );
                        if (!in_array('supplier', $enabledMenuLink) && auth()->user()->can('supplier.view') && in_array('supplier_module', $enabled_modules)) {
                            $sub->url(
                                action('ContactController@index', ['type' => 'supplier']),
                                __('report.list_supplier'),
                                ['icon' => 'fa fas fa-star', 'active' => request()->input('type') == 'supplier']
                            );
                        }

                        if (!in_array('map', $enabledMenuLink) && !empty(env('GOOGLE_MAP_API_KEY')) && in_array('direct_sales_module', $enabled_modules)) {
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
                    auth()->user()->can('unit.create') || auth()->user()->can('category.create'))) {
                $menu->dropdown(__('sale.products'),
                    function ($sub) use ($enabled_modules, $common_settings, $enable_brand, $enabledMenuLink) {
                        if (auth()->user()->can('product.view') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('ProductController@index'),
                                __('lang_v1.list_products'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'products' && request()->segment(2) == '']
                            );
                        }
                        if (auth()->user()->can('product.create') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('ProductController@create'),
                                __('product.add_product'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'products' && request()->segment(2) == 'create']
                            );
                        }

                        /* ! */
                        if (!in_array('print_labels', $enabledMenuLink) && auth()->user()->can('product.view') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('LabelsController@show'),
                                __('barcode.print_labels'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'labels' && request()->segment(2) == 'show']
                            );
                        } /* */

                        /* ! variation */
                        if (in_array('variations', $enabledMenuLink) && auth()->user()->can('product.create') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('VariationTemplateController@index'),
                                __('product.variations'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'variation-templates']
                            );

                        }
                        if (!in_array('import_products', $enabledMenuLink) && auth()->user()->can('product.create') && in_array('product_module', $enabled_modules)) {
                            /* ! */
                            $sub->url(
                                action('ImportProductsController@index'),
                                __('product.import_products'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-products']
                            ); /* */

                        }

                        /* ! */
                        if (!in_array('import_opening_stock', $enabledMenuLink) && auth()->user()->can('product.opening_stock') && in_array('inventory_module', $enabled_modules)) {
                            $sub->url(
                                action('ImportOpeningStockController@index'),
                                __('lang_v1.import_opening_stock'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-opening-stock']
                            );
                        }
                        /* ! */
                        if (in_array('selling_price_group', $enabledMenuLink) && auth()->user()->can('product.create') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('SellingPriceGroupController@index'),
                                __('lang_v1.selling_price_group'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'selling-price-group']
                            );
                        } /* */
                        if ((auth()->user()->can('unit.view') || auth()->user()->can('unit.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('UnitController@index'),
                                __('unit.units'),
                                ['icon' => 'fa fas fa-balance-scale', 'active' => request()->segment(1) == 'units']
                            );
                        }
                        if ((auth()->user()->can('category.view') || auth()->user()->can('category.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('TaxonomyController@index') . '?type=product',
                                __('category.categories'),
                                ['icon' => 'fa fas fa-tags', 'active' => request()->segment(1) == 'taxonomies' && request()->get('type') == 'product']
                            );
                        }
                        /* ! */
                        if ($enable_brand == 1 && (auth()->user()->can('brand.view') || auth()->user()->can('brand.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('BrandController@index'),
                                __('brand.brands'),
                                ['icon' => 'fa fas fa-gem', 'active' => request()->segment(1) == 'brands']
                            );
                        }

                        if (array_key_exists('enable_product_warranty', $common_settings) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('WarrantyController@index'),
                                __('lang_v1.warranties'),
                                ['icon' => 'fa fas fa-shield-alt', 'active' => request()->segment(1) == 'warranties']
                            );
                        }

                        // modifiers
                        if (in_array('product_module', $enabled_modules) && (auth()->user()->can('product.view') || auth()->user()->can('product.create'))) {
                            $sub->url(
                                action('Restaurant\ModifierSetsController@index'),
                                __('restaurant.modifiers'),
                                ['icon' => 'fa fas fa-pizza-slice', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'modifiers']
                            );
                        }
                        // modifier Options
                        if (in_array('product_module', $enabled_modules) && (auth()->user()->can('product.view') || auth()->user()->can('product.create'))) {
                            $sub->url(
                                '/modules/modifiers-option',
                                __('restaurant.modifier_option'),
                                ['icon' => 'fa fas fa-pizza-slice', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'modifiers-option']
                            );
                        }

                        /* */
                    },
                    ['icon' => 'fa fas fa-cubes', 'id' => 'tour_step5']
                )->order(20);
            }

            //Purchase dropdown
            /* ! */
            if (!in_array('purchases', $enabledMenuLink) && in_array('purchasing_module', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create') || auth()->user()->can('purchase.update'))) {
                $menu->dropdown(
                    __('purchase.purchases'),
                    function ($sub) use ($enabled_modules) {
                        if ((auth()->user()->can('purchase.view') || auth()->user()->can('view_own_purchase')) && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('PurchaseController@index'),
                                __('purchase.list_purchase'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('purchase.create') && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('PurchaseController@create'),
                                __('purchase.add_purchase'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == 'create']
                            );
                        }
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
            } /* */
            //Sell dropdown
            if ((in_array('direct_sales_module', $enabled_modules) || in_array('point_of_sales_module', $enabled_modules) || in_array('barcode_pos', $enabled_modules)) &&
                (auth()->user()->can('sell.view') || auth()->user()->can('sell.add') || auth()->user()->can('sell.pos.view') || auth()->user()->can('sell.bpos.view') || auth()->user()->can('sell.return'))) {
                $menu->dropdown(
                    __('sale.sale'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {
                        if ((auth()->user()->can('sell.view') || auth()->user()->can('sell.view_all') || auth()->user()->can('sell.bpos.view') || auth()->user()->can('sell.pos.view')
                            || auth()->user()->can('sell.pos.view_branch_sell') || auth()->user()->can('sell.bpos.view_branch_sell'))) {
                            $sub->url(
                                action('SellController@index'),
                                __('lang_v1.all_sales'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == null]
                            );
                        }
                        if (in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.add')) {
                            $sub->url(
                                action('SellController@create'),
                                __('sale.add_sale'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'create' && empty(request()->get('status'))]
                            );
                        }
                        if (!in_array('list_pos', $enabledMenuLink) && auth()->user()->can('sell.view') && in_array('direct_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('SellPosController@index'),
                                __('sale.list_pos'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == null]
                            );
                        }
                        if (!in_array('pos_sale', $enabledMenuLink) && auth()->user()->can('sell.add') && in_array('point_of_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('SellPosController@create'),
                                __('sale.pos_sale'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == 'create']
                            );
                        }
                        if (auth()->user()->can('sell.pos.add') && in_array('point_of_sales_module', $enabled_modules)) {
                            $sub->url(
                                '/pos/new',
                                __('sale.new_pos_sale'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == 'new']
                            );
                        }
                        /* ! */
                        if (auth()->user()->can('list_drafts') && in_array('direct_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('SellController@getDrafts'),
                                __('lang_v1.list_drafts'),
                                ['icon' => 'fa fas fa-pen-square', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'drafts']
                            );
                        }
                        if (!in_array('add_quotation', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && (auth()->user()->can('sell.add') || auth()->user()->can('sell.add_quotation'))) {
                            $sub->url(
                                action('SellController@create', ['status' => 'quotation']),
                                __('lang_v1.add_quotation'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->get('status') == 'quotation']
                            );
                        }
                        if (!in_array('list_quotations', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.list_quotations')) {
                            $sub->url(
                                action('SellController@getQuotations'),
                                __('lang_v1.list_quotations'),
                                ['icon' => 'fa fas fa-pen-square', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'quotations']
                            );
                        }

                        if (!in_array('list_sell_return', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.return')) {
                            $sub->url(
                                action('SellReturnController@index'),
                                __('lang_v1.list_sell_return'),
                                ['icon' => 'fa fas fa-undo', 'active' => request()->segment(1) == 'sell-return' && request()->segment(2) == null]
                            );
                        }

                        if (!in_array('shipments', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.access_shipping')) {
                            $sub->url(
                                action('SellController@shipments'),
                                __('lang_v1.shipments'),
                                ['icon' => 'fa fas fa-truck', 'active' => request()->segment(1) == 'shipments']
                            );
                        }

                        if (auth()->user()->can('discount.access') && in_array('offer_discount_module', $enabled_modules)) {
                            $sub->url(
                                action('DiscountController@index'),
                                __('lang_v1.discounts'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(1) == 'discount']
                            );
                        }
                        if (!in_array('subscriptions', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.add_edit_subscription')) {
                            $sub->url(
                                action('SellPosController@listSubscriptions'),
                                __('lang_v1.subscriptions'),
                                ['icon' => 'fa fas fa-recycle', 'active' => request()->segment(1) == 'subscriptions']
                            );
                        }

                        if (!in_array('import_sales', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('sell.import_sales')) {
                            $sub->url(
                                action('ImportSalesController@index'),
                                __('lang_v1.import_sales'),
                                ['icon' => 'fa fas fa-file-import', 'active' => request()->segment(1) == 'import-sales']
                            );
                        } /* */
                    },
                    ['icon' => 'fa fas fa-arrow-circle-up', 'id' => 'tour_step7']
                )->order(30);
            }

            //Stock transfer dropdown
            if (!in_array('stock_transfers', $enabledMenuLink) && in_array('inventory_module', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))) {
                $menu->dropdown(
                    __('lang_v1.stock_transfers'),
                    function ($sub) {
                        if (auth()->user()->can('purchase.view')) {
                            $sub->url(
                                action('StockTransferController@index'),
                                __('lang_v1.list_stock_transfers'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action('StockTransferController@create'),
                                __('lang_v1.add_stock_transfer'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-truck']
                )->order(35);
            }
            /* ! */
            //stock adjustment dropdown
            if (!in_array('stock_adjustment', $enabledMenuLink) && in_array('inventory_module', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))) {
                $menu->dropdown(
                    __('stock_adjustment.stock_adjustment'),
                    function ($sub) {
                        if (auth()->user()->can('purchase.view')) {
                            $sub->url(
                                action('StockAdjustmentController@index'),
                                __('stock_adjustment.list'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == null]
                            );
                        }

                        if (auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action('StockAdjustmentController@create'),
                                __('stock_adjustment.add'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-database']
                )->order(40);
            }
            /* */
            //Expense dropdown
            if (in_array('expenses_module', $enabled_modules) && (auth()->user()->can('expense.access') || auth()->user()->can('view_own_expense'))) {
                $menu->dropdown(
                    __('expense.expenses'),
                    function ($sub) {
                        $sub->url(
                            action('ExpenseController@index'),
                            __('lang_v1.list_expenses'),
                            ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == null]
                        );
                        $sub->url(
                            action('ExpenseController@create'),
                            __('expense.add_expense'),
                            ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == 'create']
                        );
                        $sub->url(
                            action('ExpenseCategoryController@index'),
                            __('expense.expense_categories'),
                            ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'expense-categories']
                        );
                    },
                    ['icon' => 'fa fas fa-minus-circle']
                )->order(45);
            }
            //Accounts dropdown
            if (!in_array('payment_accounts', $enabledMenuLink) && auth()->user()->can('account.access') && in_array('payment_account_module', $enabled_modules)) {
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
                        $sub->url(
                            action('AccountReportsController@paymentAccountReport'),
                            __('account.payment_account_report'),
                            ['icon' => 'fa fas fa-file-alt', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'payment-account-report']
                        );
                    },
                    ['icon' => 'fa fas fa-money-check-alt']
                )->order(50);
            }

            //Reports dropdown
            if ((in_array('basic_reports_module', $enabled_modules) || in_array('advance_reports_module', $enabled_modules)) && (auth()->user()->can('purchase_n_sell_report.view') || auth()->user()->can('contacts_report.view')
                || auth()->user()->can('stock_report.view') || auth()->user()->can('tax_report.view')
                || auth()->user()->can('trending_product_report.view') || auth()->user()->can('sales_representative.view') || auth()->user()->can('register_report.view')
                || auth()->user()->can('expense_report.view'))) {
                $menu->dropdown(
                    __('report.reports'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {
                        if (!in_array('register_report', $enabledMenuLink) && auth()->user()->can('register_report.view') && in_array('basic_reports_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getRegisterReport'),
                                __('report.register_report'),
                                ['icon' => 'fa fas fa-briefcase', 'active' => request()->segment(2) == 'register-report']
                            );
                        }

                        if (!in_array('tax_report', $enabledMenuLink) && auth()->user()->can('tax_report.view') && in_array('basic_reports_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getTaxReport'),
                                __('report.tax_report'),
                                ['icon' => 'fa fas fa-percent', 'active' => request()->segment(2) == 'tax-report']
                            );
                        }

                        if (!in_array('sell_payment_report', $enabledMenuLink) && auth()->user()->can('purchase_n_sell_report.view') && in_array('advance_reports_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@sellPaymentReport'),
                                __('lang_v1.sell_payment_report'),
                                ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'sell-payment-report']
                            );
                        }
                        if (!in_array('purchase_payment_report', $enabledMenuLink) && auth()->user()->can('purchase_n_sell_report.view') && in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@purchasePaymentReport'),
                                __('lang_v1.purchase_payment_report'),
                                ['icon' => 'fa fas fa-search-dollar', 'active' => request()->segment(2) == 'purchase-payment-report']
                            );
                        }
                        if (!in_array('sales_report_by_branch', $enabledMenuLink) && auth()->user()->can('purchase_n_sell_report.view') && in_array('advance_reports_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getSalesByBranchReport'),
                                __('report.sales_report_by_branch'),
                                ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'sales-by-branch-report']
                            );
                        }

                        if (!in_array('product_sell_report_branch_by_branch', $enabledMenuLink) && auth()->user()->can('purchase_n_sell_report.view') && in_array('basic_reports_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getProductSellBranchByBranchReport'),
                                __('report.product_sell_report_branch_by_branch'),
                                ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'product-sell-branch-by-branch-report']
                            );
                        }

                        if (!in_array('profit_loss', $enabledMenuLink) && auth()->user()->can('profit_loss_report.view') && in_array('advance_reports_module', $enabled_modules) && in_array('expenses_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getProfitLoss'),
                                __('report.profit_loss'),
                                ['icon' => 'fa fas fa-file-invoice-dollar', 'active' => request()->segment(2) == 'profit-loss']
                            );
                        }
                        if ((config('constants.show_report_606') == true) && in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@purchaseReport'),
                                'Report 606 (' . __('lang_v1.purchase') . ')',
                                ['icon' => 'fa fas fa-arrow-circle-down', 'active' => request()->segment(2) == 'purchase-report']
                            );
                        }
                        if ((config('constants.show_report_607') == true) && in_array('advance_reports_module', $enabled_modules) && in_array('purchasing_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@saleReport'),
                                'Report 607 (' . __('business.sale') . ')',
                                ['icon' => 'fa fas fa-arrow-circle-up', 'active' => request()->segment(2) == 'sale-report']
                            );
                        }

                        if (!in_array('purchase_sell_report', $enabledMenuLink) && (in_array('advance_reports_module', $enabled_modules)) && (in_array('purchasing_module', $enabled_modules)) && auth()->user()->can('purchase_n_sell_report.view')) {
                            $sub->url(
                                action('ReportController@getPurchaseSell'),
                                __('report.purchase_sell_report'),
                                ['icon' => 'fa fas fa-exchange-alt', 'active' => request()->segment(2) == 'purchase-sell']
                            );
                        }

                        /* ! */
                        if (!in_array('getCustomerSuppliers', $enabledMenuLink) && auth()->user()->can('contacts_report.view') && (in_array('customer_module', $enabled_modules) || in_array('supplier_module', $enabled_modules)) && in_array('advance_reports_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getCustomerSuppliers'),
                                __('report.contacts'),
                                ['icon' => 'fa fas fa-address-book', 'active' => request()->segment(2) == 'customer-supplier']
                            );
                            $sub->url(
                                action('ReportController@getCustomerGroup'),
                                __('lang_v1.customer_groups_report'),
                                ['icon' => 'fa fas fa-users', 'active' => request()->segment(2) == 'customer-group']
                            );
                        } /*  */
                        if (auth()->user()->can('stock_report.view') && in_array('advance_reports_module', $enabled_modules) && in_array('inventory_module', $enabled_modules)) {
                            if (!in_array('stock_report', $enabledMenuLink)) {
                                $sub->url(
                                    action('ReportController@getStockReport'),
                                    __('report.stock_report'),
                                    ['icon' => 'fa fas fa-hourglass-half', 'active' => request()->segment(2) == 'stock-report']
                                );
                            }
                            if (!in_array('stock_expiry_report', $enabledMenuLink) && in_array('inventory_module', $enabled_modules) && in_array('advance_reports_module', $enabled_modules) && session('business.enable_product_expiry') == 1) {
                                $sub->url(
                                    action('ReportController@getStockExpiryReport'),
                                    __('report.stock_expiry_report'),
                                    ['icon' => 'fa fas fa-calendar-times', 'active' => request()->segment(2) == 'stock-expiry']
                                );
                            }
                            /* ! */
                            if (!in_array('lot_report', $enabledMenuLink) && in_array('inventory_module', $enabled_modules) && in_array('advance_reports_module', $enabled_modules) && session('business.enable_lot_number') == 1) {
                                $sub->url(
                                    action('ReportController@getLotReport'),
                                    __('lang_v1.lot_report'),
                                    ['icon' => 'fa fas fa-hourglass-half', 'active' => request()->segment(2) == 'lot-report']
                                );
                            }

                            if (!in_array('stock_adjustment_report', $enabledMenuLink) && in_array('advance_reports_module', $enabled_modules) && in_array('inventory_module', $enabled_modules)) {
                                $sub->url(
                                    action('ReportController@getStockAdjustmentReport'),
                                    __('report.stock_adjustment_report'),
                                    ['icon' => 'fa fas fa-sliders-h', 'active' => request()->segment(2) == 'stock-adjustment-report']
                                );
                            } /* */
                        }

                        if (!in_array('trending_products', $enabledMenuLink) && in_array('advance_reports_module', $enabled_modules) && auth()->user()->can('trending_product_report.view')) {
                            $sub->url(
                                action('ReportController@getTrendingProducts'),
                                __('report.trending_products'),
                                ['icon' => 'fa fas fa-chart-line', 'active' => request()->segment(2) == 'trending-products']
                            );
                        }
                        /* ! */

                        if (!in_array('items_report', $enabledMenuLink) && auth()->user()->can('purchase_n_sell_report.view') && in_array('advance_reports_module', $enabled_modules)) {

                            $sub->url(
                                action('ReportController@itemsReport'),
                                __('lang_v1.items_report'),
                                ['icon' => 'fa fas fa-tasks', 'active' => request()->segment(2) == 'items-report']
                            );
                        }
                        if (!in_array('product_purchase_report', $enabledMenuLink) && auth()->user()->can('purchase_n_sell_report.view') && in_array('purchasing_module', $enabled_modules) && in_array('advance_reports_module', $enabled_modules)) {

                            $sub->url(
                                action('ReportController@getproductPurchaseReport'),
                                __('lang_v1.product_purchase_report'),
                                ['icon' => 'fa fas fa-arrow-circle-down', 'active' => request()->segment(2) == 'product-purchase-report']
                            );
                        }

                        /* */
                        if (!in_array('product_sell_report', $enabledMenuLink) && in_array('basic_reports_module', $enabled_modules) && auth()->user()->can('purchase_n_sell_report.view')) {
                            $sub->url(
                                action('ReportController@getproductSellReport'),
                                __('lang_v1.product_sell_report'),
                                ['icon' => 'fa fas fa-arrow-circle-up', 'active' => request()->segment(2) == 'product-sell-report']
                            );
                        }
                        /* ! */

                        if (!in_array('expense_report', $enabledMenuLink) && in_array('advance_reports_module', $enabled_modules) && in_array('expenses_module', $enabled_modules) && auth()->user()->can('expense_report.view')) {
                            $sub->url(
                                action('ReportController@getExpenseReport'),
                                __('report.expense_report'),
                                ['icon' => 'fa fas fa-search-minus', 'active' => request()->segment(2) == 'expense-report']
                            );
                        }

                        if (!in_array('sales_representative', $enabledMenuLink) && in_array('user_module', $enabled_modules) && in_array('advance_reports_module', $enabled_modules) && auth()->user()->can('sales_representative.view')) {
                            $sub->url(
                                action('ReportController@getSalesRepresentativeReport'),
                                __('report.sales_representative'),
                                ['icon' => 'fa fas fa-user', 'active' => request()->segment(2) == 'sales-representative-report']
                            );
                        }
                        if (!in_array('table_report', $enabledMenuLink) && in_array('basic_reports_module', $enabled_modules) && auth()->user()->can('purchase_n_sell_report.view') && in_array('table_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getTableReport'),
                                __('restaurant.table_report'),
                                ['icon' => 'fa fas fa-table', 'active' => request()->segment(2) == 'table-report']
                            );
                        }

                        if (!in_array('service_staff_report', $enabledMenuLink) && in_array('basic_reports_module', $enabled_modules) && auth()->user()->can('sales_representative.view') && in_array('service_staff_module', $enabled_modules)) {
                            $sub->url(
                                action('ReportController@getServiceStaffReport'),
                                __('restaurant.service_staff_report'),
                                ['icon' => 'fa fas fa-user-secret', 'active' => request()->segment(2) == 'service-staff-report']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-chart-bar', 'id' => 'tour_step8']
                )->order(55);
            }

            //Backup menu
            if (!in_array('backup', $enabledMenuLink) && auth()->user()->can('backup')) {
                $menu->url(action('BackUpController@index'), __('lang_v1.backup'), ['icon' => 'fa fas fa-hdd', 'active' => request()->segment(1) == 'backup'])->order(60);
            }

            //Modules menu
            if (!in_array('modules', $enabledMenuLink) && auth()->user()->can('manage_modules')) {
                $menu->url(action('Install\ModulesController@index'), __('lang_v1.modules'), ['icon' => 'fa fas fa-plug', 'active' => request()->segment(1) == 'manage-modules'])->order(60);
            }

            //Booking menu
            if (!in_array('bookings', $enabledMenuLink) && in_array('table_module', $enabled_modules) && (auth()->user()->can('crud_all_bookings') || auth()->user()->can('crud_own_bookings'))) {
                $menu->url(action('Restaurant\BookingController@index'), __('restaurant.bookings'), ['icon' => 'fas fa fa-calendar-check', 'active' => request()->segment(1) == 'bookings'])->order(65);
            }

            //Kitchen menu
            if ($showKitchen && !in_array('kitchen', $enabledMenuLink) && in_array('kitchen_module', $enabled_modules)) {
                $menu->url(action('Restaurant\KitchenController@index'), __('restaurant.kitchen'), ['icon' => 'fa fas fa-fire', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'kitchen'])->order(70);
            }

            //Service Staff menu
            if (!in_array('orders', $enabledMenuLink) && in_array('service_staff_module', $enabled_modules)) {
                $menu->url(action('Restaurant\OrderController@index'), __('restaurant.orders'), ['icon' => 'fa fas fa-list-alt', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'orders'])->order(75);
            }
            /* ! */
            //Notification template menu
            if ((in_array('user_module', $enabled_modules) || in_array('direct_sales_module', $enabled_modules) || in_array('product_module', $enabled_modules) || in_array('point_of_sales_module', $enabled_modules))
                && !in_array('notification_templates', $enabledMenuLink) && auth()->user()->can('send_notifications')) {
                $menu->url(action('NotificationTemplateController@index'), __('lang_v1.notification_templates'), ['icon' => 'fa fas fa-envelope', 'active' => request()->segment(1) == 'notification-templates'])->order(80);
            }
            /* */
            //Settings Dropdown
            if ((
                auth()->user()->can('business_settings.access') ||
                auth()->user()->can('barcode_settings.access') ||
                auth()->user()->can('invoice_settings.access') ||
                auth()->user()->can('tax_rate.view') ||
                auth()->user()->can('tax_rate.create') ||
                auth()->user()->can('access_package_subscriptions'))) {
                $menu->dropdown(
                    __('business.settings'),
                    function ($sub) use ($enabled_modules, $enabledMenuLink) {

                        if (auth()->user()->can('business_settings.access')) {
                            /* ! */
                            $sub->url(
                                action('BusinessController@getBusinessSettings'),
                                __('business.business_settings'),
                                ['icon' => 'fa fas fa-cogs', 'active' => request()->segment(1) == 'business', 'id' => "tour_step2"]
                            );

                        }
                        if (auth()->user()->can('business_settings.access') && in_array('product_module', $enabled_modules)) {
                            /* */
                            $sub->url(
                                action('BusinessLocationController@index'),
                                __('business.business_locations'),
                                ['icon' => 'fa fas fa-map-marker', 'active' => request()->segment(1) == 'business-location']
                            );
                        }

                        if (auth()->user()->can('invoice_settings.access') && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('InvoiceSchemeController@index'),
                                __('invoice.invoice_settings'),
                                ['icon' => 'fa fas fa-file', 'active' => in_array(request()->segment(1), ['invoice-schemes', 'invoice-layouts'])]
                            );
                        }

                        /* ! */
                        if (!in_array('barcode_settings', $enabledMenuLink) && auth()->user()->can('barcode_settings.access') && in_array('direct_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('BarcodeController@index'),
                                __('barcode.barcode_settings'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'barcodes']
                            );
                        } /* */

                        /* ! */
                        if (!in_array('receipt_printers', $enabledMenuLink) && in_array('product_module', $enabled_modules) && auth()->user()->can('access_printers')) {
                            $sub->url(
                                action('PrinterController@index'),
                                __('printer.receipt_printers'),
                                ['icon' => 'fa fas fa-share-alt', 'active' => request()->segment(1) == 'printers']
                            );
                        }
                        /* */
                        if ((auth()->user()->can('tax_rate.view') || auth()->user()->can('tax_rate.create')) && in_array('product_module', $enabled_modules)) {
                            $sub->url(
                                action('TaxRateController@index'),
                                __('tax_rate.tax_rates'),
                                ['icon' => 'fa fas fa-bolt', 'active' => request()->segment(1) == 'tax-rates']
                            );
                        }

                        if (!in_array('tables', $enabledMenuLink) && in_array('table_module', $enabled_modules) && auth()->user()->can('access_tables')) {
                            $sub->url(
                                action('Restaurant\TableController@index'),
                                __('restaurant.tables'),
                                ['icon' => 'fa fas fa-table', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'tables']
                            );
                        }

                        if (!in_array('clinet_app', $enabledMenuLink) && in_array('point_of_sales_module', $enabled_modules)) {
                            $sub->url(
                                action('ClientAppController@index'),
                                __('business.clientApp'),
                                ['icon' => 'fa fas fa-shipping-fast', 'active' => request()->segment(1) == 'client-app']
                            );
                        }

                        if (!in_array('types_of_service', $enabledMenuLink) && in_array('direct_sales_module', $enabled_modules) && auth()->user()->can('access_types_of_service')) {
                            $sub->url(
                                action('TypesOfServiceController@index'),
                                __('lang_v1.types_of_service'),
                                ['icon' => 'fa fas fa-user-circle', 'active' => request()->segment(1) == 'types-of-service']
                            );
                        }
                    },
                    ['icon' => 'fa fas fa-cog', 'id' => 'tour_step3']
                )->order(85);
            }

        });

        //Add menus from modules
        $moduleUtil = new ModuleUtil;
        $moduleUtil->getModuleData('modifyAdminMenu');

        return $next($request);
    }
}