<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

/* Route::get('/random', function () {
return view("superadmin::subscription.index");
}); */

Route::get('/query', 'SellController@runrawQuery');

Route::get('/test', function () {
    $a = Artisan::call('dump-autoload');
    return Artisan::output();
});

// Clear application cache:

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    return Artisan::output();

});
//Clear route cache:

Route::get('/route-cache', function () {
    Artisan::call('route:cache');
    return Artisan::output();

});
//Clear config cache:

Route::get('/config-cache', function () {
    Artisan::call('config:cache');
    return Artisan::output();

});
// Clear view cache:

Route::get('/view-clear', function () {
    Artisan::call('view:clear');
    return Artisan::output();

});

/* Route::get('/print/demo', 'PrintController@demo');
Route::get('/print/test', 'PrintController@test'); */

Route::get('/pos/new', function () {
    if ($_SERVER['HTTP_HOST'] == 'localhost:8000') {
        return redirect("http://localhost:3000/");
    }
    return view('pos.index');
})->middleware(['auth', 'OpenedRegister', 'language'])->name('pos.new');

/* Route::post('/pos', function () {
if ($_SERVER['HTTP_HOST'] == 'localhost:8000') {
return redirect("http://localhost:3000/");
}
return view('pos.index');
})->middleware(['auth', 'OpenedRegister'])->name('pos'); */

include_once 'install_r.php';

Route::get('/kitchen-station', function () {
    Artisan::call('migrate', ['--path' => 'database/migrations/2021_04_23_115622_create_kitchen_stations_table.php']);
});

Route::get('/product-kitchen-station', function () {
    Artisan::call(
        'migrate',
        ['--path' => 'database/migrations/2021_04_26_093841_create_product_kitchen_stations_table.php']
    );
});

Route::get('/add-tax-field-in-variations', function () {
    try {
        DB::beginTransaction();
        Artisan::call(
            'migrate',
            ['--path' => 'database/migrations/2021_09_23_171801_add_tax_and_tax_type_field_in_variations_table.php']
        );
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
    }
});

Route::get('/change-receipt-printer-type-field', function () {
    try {
        DB::beginTransaction();

        DB::statement("ALTER TABLE business_locations CHANGE COLUMN receipt_printer_type receipt_printer_type ENUM('browser', 'printer', 'app') NOT NULL DEFAULT 'browser'");

        Artisan::call(
            'migrate',
            ['--path' => 'database/migrations/2021_10_29_174418_add_thermal_print_copy_field_in_business_locations_table.php']
        );

        DB::commit();
    } catch (\Exception $exception) {
        DB::rollBack();
        \Log::emergency("File:" . $exception->getFile() . "Line:" . $exception->getLine() . "Message:" . $exception->getMessage());
    }
});

Route::middleware(['setData', 'language'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Auth::routes();

    Route::get('/business/register', 'BusinessController@getRegister')->name('business.getRegister');
    Route::post('/business/register', 'BusinessController@postRegister')->name('business.postRegister');
    Route::post('/business/register/check-username', 'BusinessController@postCheckUsername')->name('business.postCheckUsername');
    Route::post('/business/register/check-email', 'BusinessController@postCheckEmail')->name('business.postCheckEmail');

    Route::get('/invoice/{token}', 'SellPosController@showInvoice')->name('show_invoice');
    Route::get('/quote/{token}', 'SellPosController@showInvoice')
        ->name('show_quote');
});

//Routes for authenticated users only
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/home/get-totals', 'HomeController@getTotals');
    Route::get('/home/product-stock-alert', 'HomeController@getProductStockAlert');
    Route::get('/home/get-purchase-totals', 'HomeController@getPurchaseTotals');
    Route::get('/home/get-transactional-totals', 'HomeController@getTransactionalTotals');
    Route::get('/home/get-sale-totals', 'HomeController@getSellTotals');
    Route::get('/home/purchase-payment-dues', 'HomeController@getPurchasePaymentDues');
    Route::get('/home/sales-payment-dues', 'HomeController@getSalesPaymentDues');

    // New Endpoints //
    Route::get('/home/home_widgets', 'HomeController@get_home_widgets')->name("home_widgets");
    Route::post('/home/chart_for_this_fy', 'HomeController@chart_for_this_fy')->name("chart_for_this_fy");
    Route::post('/home/sell_chart_one', 'HomeController@sell_chart_one')->name("sell_chart_one");

    Route::get('/calendar', 'HomeController@getCalendar')->name('calendar');
    Route::get('home/saveAgreement', 'HomeController@saveAgreement');
    Route::get('home/printIndex', 'HomeController@printIndex');
    Route::get('home/printESCPOS', 'HomeController@printESCPOS');
    Route::get('print/cmd', 'PrintESCPOSController@printCommands');
    Route::get('print/pdf', 'PrintESCPOSController@printFile');
    Route::any('WebClientPrintController', 'WebClientPrintController@processRequest');

    Route::post('/test-email', 'BusinessController@testEmailConfiguration');
    Route::post('/test-sms', 'BusinessController@testSmsConfiguration');
    Route::get('/business/settings', 'BusinessController@getBusinessSettings')->name('business.getBusinessSettings');
    Route::post('/business/update', 'BusinessController@postBusinessSettings')->name('business.postBusinessSettings');
    Route::get('/user/profile', 'UserController@getProfile')->name('user.getProfile');
    Route::post('/user/update', 'UserController@updateProfile')->name('user.updateProfile');
    Route::post('/user/update-password', 'UserController@updatePassword')->name('user.updatePassword');

    Route::resource('brands', 'BrandController');

    Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', 'TaxRateController');
    Route::resource('client-app', 'ClientAppController');

    Route::resource('units', 'UnitController');

    Route::get('/contacts/payments/{contact_id}', 'ContactController@getContactPayments');
    Route::get('/contacts/map', 'ContactController@contactMap');
    Route::get('/contacts/update-status/{id}', 'ContactController@updateStatus');
    Route::get('/contacts/stock-report/{supplier_id}', 'ContactController@getSupplierStockReport');
    Route::get('/contacts/ledger', 'ContactController@getLedger');
    Route::post('/contacts/send-ledger', 'ContactController@sendLedger');
    Route::get('/contacts/import', 'ContactController@getImportContacts')->name('contacts.import');
    Route::post('/contacts/import', 'ContactController@postImportContacts');
    Route::post('/contacts/check-contact-id', 'ContactController@checkContactId');
    Route::get('/contacts/customers', 'ContactController@getCustomers');
    Route::resource('contacts', 'ContactController');

    Route::get('taxonomies-ajax-index-page', 'TaxonomyController@getTaxonomyIndexPage');
    Route::resource('taxonomies', 'TaxonomyController');
    Route::get('taxonomies/{media_id}/delete-category-image', 'TaxonomyController@deleteCategoryMedia');
    Route::post('taxonomies/add_modifiers/', 'TaxonomyController@addCategoryModifiers');
    Route::post('taxonomies/add_category_locations/', 'TaxonomyController@addCategoryLocations');
    Route::get('/taxonomies/loadlocationsTable/{id}', 'TaxonomyController@loadlocationsTable');
    Route::get('/taxonomies/loadModifiersTable/{id}', 'TaxonomyController@loadModifiersTable');
    Route::resource('variation-templates', 'VariationTemplateController');

    Route::get('/products/stock-history/{id}', 'ProductController@productStockHistory');
    Route::get('/delete-media/{media_id}', 'ProductController@deleteMedia');
    Route::post('/products/mass-deactivate', 'ProductController@massDeactivate');
    Route::get('/products/activate/{id}', 'ProductController@activate');
    Route::get('/products/view-product-group-price/{id}', 'ProductController@viewGroupPrice');
    Route::get('/products/add-selling-prices/{id}', 'ProductController@addSellingPrices');
    Route::post('/products/save-selling-prices', 'ProductController@saveSellingPrices');
    Route::post('/products/mass-delete', 'ProductController@massDestroy');
    Route::get('/products/view/{id}', 'ProductController@view');
    Route::get('/products/editCustomProductPrice/{product_id}/{business_id}', 'ProductController@editCustomProductPrice');
    Route::get('/products/list', 'ProductController@getProducts');
    Route::get('/products/list-material', 'ProductController@getMaterialProducts');

    Route::get('/products/list-no-variation', 'ProductController@getProductsWithoutVariations');
    Route::post('/products/bulk-edit', 'ProductController@bulkEdit');
    Route::post('/products/bulk-update', 'ProductController@bulkUpdate');
    Route::post('/products/bulk-update-location', 'ProductController@updateProductLocation');
    Route::get('/products/get-product-to-edit/{product_id}', 'ProductController@getProductToEdit');

    Route::post('/products/get_sub_categories', 'ProductController@getSubCategories');
    Route::get('/products/get_sub_units', 'ProductController@getSubUnits');
    Route::post('/products/product_form_part', 'ProductController@getProductVariationFormPart');
    Route::post('/products/custom_product_price_form_part', 'ProductController@getProductCustomPriceFormPart');
    Route::post('/products/get_product_variation_row', 'ProductController@getProductVariationRow');
    Route::post('/products/get_variation_template', 'ProductController@getVariationTemplate');
    Route::get('/products/get_variation_value_row', 'ProductController@getVariationValueRow');
    Route::post('/products/check_product_sku', 'ProductController@checkProductSku');
    Route::get('/products/quick_add', 'ProductController@quickAdd');
    Route::get('/products/add_modifiers/{id}', 'ProductController@adModifiers');
    Route::get('/products/add_product_custom_price/{id}', 'ProductController@addProductCustomPrice');
    Route::get('/products/edit_product_custom_price/{product_id}/{business_id}', 'ProductController@UpdateProductCustomPrice');
    Route::get('/products/delete_product_custom_price/{product_id}/{business_id}', 'ProductController@deleteCustomProductPrice');
    Route::get('/products/loadModifiersTable/{id}', 'ProductController@loadModifiersTable');
    Route::get('/products/loadCustomProductPriceTable/{id}', 'ProductController@loadCustomProductPriceTable');
    Route::get('/products/loadIngredientsRowTable/{id}', 'ProductController@loadIngredientsRowTable');

    Route::post('/products/save_quick_product', 'ProductController@saveQuickProduct');
    Route::get('/products/get-combo-product-entry-row', 'ProductController@getComboProductEntryRow');

    Route::resource('products', 'ProductController');

    Route::post('/purchases/update-status', 'PurchaseController@updateStatus');
    Route::get('/purchases/get_products', 'PurchaseController@getProducts');
    Route::get('/purchases/get_suppliers', 'PurchaseController@getSuppliers');
    Route::post('/purchases/get_purchase_entry_row', 'PurchaseController@getPurchaseEntryRow');
    Route::post('/purchases/check_ref_number', 'PurchaseController@checkRefNumber');
    Route::resource('purchases', 'PurchaseController')->except(['show']);

    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::resource('sells', 'SellController')->except(['show']);

    Route::get('/import-sales', 'ImportSalesController@index');
    Route::post('/import-sales/preview', 'ImportSalesController@preview');
    Route::post('/import-sales', 'ImportSalesController@import');
    Route::get('/revert-sale-import/{batch}', 'ImportSalesController@revertSaleImport');

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', 'SellPosController@getProductRow');
    Route::post('/sells/pos/get_payment_row', 'SellPosController@getPaymentRow');
    Route::post('/sells/pos/get-reward-details', 'SellPosController@getRewardDetails');
    Route::get('/sells/pos/get-recent-transactions', 'SellPosController@getRecentTransactions');
    Route::get('/sells/pos/get-product-suggestion', 'SellPosController@getProductSuggestion');
    Route::get('/sells/pos/get-featured-products/{location_id}', 'SellPosController@getFeaturedProducts');
    Route::resource('pos', 'SellPosController');
    Route::post('/pos/suspended-sales', 'SellPosController@deleteAllSuspendedSales');

    Route::resource('roles', 'RoleController');

    Route::resource('users', 'ManageUserController');

    Route::resource('group-taxes', 'GroupTaxController');

    Route::get('/barcodes/set_default/{id}', 'BarcodeController@setDefault');
    Route::resource('barcodes', 'BarcodeController');

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', 'InvoiceSchemeController@setDefault');
    Route::resource('invoice-schemes', 'InvoiceSchemeController');

    //Call Number schemes..
    Route::resource('call-number-schemes', 'InvoiceSchemeController@callNumberSchemes');

    //Print Labels
    Route::get('/labels/show', 'LabelsController@show');
    Route::get('/labels/add-product-row', 'LabelsController@addProductRow');
    Route::get('/labels/preview', 'LabelsController@preview');

    //Reports...
    Route::get('/reports/purchase-report', 'ReportController@purchaseReport');
    Route::get('/reports/sale-report', 'ReportController@saleReport');
    Route::get('/reports/service-staff-report', 'ReportController@getServiceStaffReport');
    Route::get('/reports/service-staff-line-orders', 'ReportController@serviceStaffLineOrders');
    Route::get('/reports/table-report', 'ReportController@getTableReport');
    Route::get('/reports/profit-loss', 'ReportController@getProfitLoss');
    Route::get('/reports/get-opening-stock', 'ReportController@getOpeningStock');
    Route::get('/reports/purchase-sell', 'ReportController@getPurchaseSell');
    Route::get('/reports/customer-supplier', 'ReportController@getCustomerSuppliers');
    Route::get('/reports/stock-report', 'ReportController@getStockReport');
    Route::get('/reports/stock-details', 'ReportController@getStockDetails');
    Route::get('/reports/tax-report', 'ReportController@getTaxReport');
    Route::get('/reports/tax-details', 'ReportController@getTaxDetails');

    Route::get('/reports/trending-products', 'ReportController@getTrendingProducts');
    // New Endpoints
    Route::get('/reports/trending-products-data', 'ReportController@getTrendingProductsData');
    Route::get('/reports/trending-products-categories', 'ReportController@getCategories');
    Route::get('/reports/trending-products-brands', 'ReportController@getBrands');
    Route::get('/reports/trending-products-units', 'ReportController@getUnits');
    Route::get('/reports/trending-products-locations', 'ReportController@getBusinessLocations');
    Route::get('/reports/trending-products-chart', 'ReportController@getTrendingProductsData');

    Route::get('/reports/expense-report', 'ReportController@getExpenseReport');
    Route::get('/reports/stock-adjustment-report', 'ReportController@getStockAdjustmentReport');
    Route::get('/reports/register-report', 'ReportController@getRegisterReport');
    Route::get('/reports/sales-representative-report', 'ReportController@getSalesRepresentativeReport');
    Route::get('/reports/sales-representative-total-expense', 'ReportController@getSalesRepresentativeTotalExpense');
    Route::get('/reports/sales-representative-total-sell', 'ReportController@getSalesRepresentativeTotalSell');
    Route::get('/reports/sales-representative-total-commission', 'ReportController@getSalesRepresentativeTotalCommission');
    Route::get('/reports/stock-expiry', 'ReportController@getStockExpiryReport');
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', 'ReportController@getStockExpiryReportEditModal');
    Route::post('/reports/stock-expiry-update', 'ReportController@updateStockExpiryReport')->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', 'ReportController@getCustomerGroup');
    Route::get('/reports/product-purchase-report', 'ReportController@getproductPurchaseReport');
    Route::get('/reports/product-sell-report', 'ReportController@getproductSellReport');
    Route::get('/reports/product-sell-report-with-purchase', 'ReportController@getproductSellReportWithPurchase');
    Route::get('/reports/product-sell-grouped-branch-by-branch-report', 'ReportController@getproductSellGroupedBranchByBranchReport');
    Route::get('/reports/category-sell-grouped-branch-by-branch-report', 'ReportController@getcategorySellGroupedBranchByBranchReport');
    Route::get('/reports/product-sell-grouped-report', 'ReportController@getproductSellGroupedReport');
    Route::get('/reports/product-sell-branch-by-branch-report', 'ReportController@getProductSellBranchByBranchReport');
    Route::get('/reports/sales-by-branch-report', 'ReportController@getSalesByBranchReport');
    Route::get('/reports/lot-report', 'ReportController@getLotReport');
    Route::get('/reports/purchase-payment-report', 'ReportController@purchasePaymentReport');
    Route::get('/reports/sell-payment-report', 'ReportController@sellPaymentReport');
    Route::get('/reports/product-stock-details', 'ReportController@productStockDetails');
    Route::get('/reports/adjust-product-stock', 'ReportController@adjustProductStock');
    Route::get('/reports/get-profit/{by?}', 'ReportController@getProfit');
    Route::get('/reports/items-report', 'ReportController@itemsReport');
    Route::get('/reports/get-stock-value', 'ReportController@getStockValue');

    Route::get('business-location/activate-deactivate/{location_id}', 'BusinessLocationController@activateDeactivateLocation');

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', 'LocationSettingsController@index')->name('settings');
        Route::post('settings', 'LocationSettingsController@updateSettings')->name('settings_update');
    });

    Route::prefix('business-location/{kitchen_id}/{location_id}')->name('location.')->group(function () {
        Route::get('assign_kitchen_categories', 'LocationSettingsController@loadKitchenCategoryTable')->name('assign_kitchen_categories');
    });
    Route::post('business-location/add_category_kitchen/', 'LocationSettingsController@addCategoryKitchens');

    //Business Locations...
    Route::post('business-location/check-location-id', 'BusinessLocationController@checkLocationId');
    Route::resource('business-location', 'BusinessLocationController');

    //Invoice layouts..
    Route::resource('invoice-layouts', 'InvoiceLayoutController');

    //Expense Categories...
    Route::resource('expense-categories', 'ExpenseCategoryController');

    //Expenses...
    Route::resource('expenses', 'ExpenseController');

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', 'TransactionPaymentController@showChildPayments');
    Route::get('/payments/view-payment/{payment_id}', 'TransactionPaymentController@viewPayment');
    Route::get('/payments/add_payment/{transaction_id}', 'TransactionPaymentController@addPayment');
    Route::get('/payments/pay-contact-due/{contact_id}', 'TransactionPaymentController@getPayContactDue');
    Route::post('/payments/pay-contact-due', 'TransactionPaymentController@postPayContactDue');
    Route::resource('payments', 'TransactionPaymentController');

    //Printers...
    Route::resource('printers', 'PrinterController');

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', 'StockAdjustmentController@removeExpiredStock');
    Route::post('/stock-adjustments/get_product_row', 'StockAdjustmentController@getProductRow');
    Route::resource('stock-adjustments', 'StockAdjustmentController');

    Route::get('/cash-register/register-details', 'CashRegisterController@getRegisterDetails');
    Route::get('/cash-register/close-register/{id?}', 'CashRegisterController@getCloseRegister');
    Route::post('/cash-register/close-register', 'CashRegisterController@postCloseRegister');
    Route::resource('cash-register', 'CashRegisterController');

    //Import products
    Route::get('/import-products', 'ImportProductsController@index');
    Route::post('/import-products/store', 'ImportProductsController@store');

    //Sales Commission Agent
    Route::resource('sales-commission-agents', 'SalesCommissionAgentController');

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', 'StockTransferController@printInvoice');
    Route::post('stock-transfers/update-status/{id}', 'StockTransferController@updateStatus');
    Route::resource('stock-transfers', 'StockTransferController');

    Route::get('/opening-stock/add/{product_id}', 'OpeningStockController@add');
    Route::post('/opening-stock/save', 'OpeningStockController@save');

    //Customer Groups
    Route::resource('customer-group', 'CustomerGroupController');

    //Import opening stock
    Route::get('/import-opening-stock', 'ImportOpeningStockController@index');
    Route::post('/import-opening-stock/store', 'ImportOpeningStockController@store');

    //Sell return
    Route::resource('sell-return', 'SellReturnController');
    Route::get('sell-return/get-product-row', 'SellReturnController@getProductRow');
    Route::get('/sell-return/print/{id}', 'SellReturnController@printInvoice');
    Route::get('/sell-return/add/{id}', 'SellReturnController@add');

    //Backup
    Route::get('backup/download/{file_name}', 'BackUpController@download');
    Route::get('backup/delete/{file_name}', 'BackUpController@delete');
    Route::resource('backup', 'BackUpController', ['only' => [
        'index', 'create', 'store',
    ]]);

    Route::get('selling-price-group/activate-deactivate/{id}', 'SellingPriceGroupController@activateDeactivate');
    Route::get('export-selling-price-group', 'SellingPriceGroupController@export');
    Route::post('import-selling-price-group', 'SellingPriceGroupController@import');

    Route::resource('selling-price-group', 'SellingPriceGroupController');

    Route::resource('notification-templates', 'NotificationTemplateController')->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', 'NotificationController@getTemplate');
    Route::post('notification/send', 'NotificationController@send');

    Route::post('/purchase-return/update', 'CombinedPurchaseReturnController@update');
    Route::get('/purchase-return/edit/{id}', 'CombinedPurchaseReturnController@edit');
    Route::post('/purchase-return/save', 'CombinedPurchaseReturnController@save');
    Route::post('/purchase-return/get_product_row', 'CombinedPurchaseReturnController@getProductRow');
    Route::get('/purchase-return/create', 'CombinedPurchaseReturnController@create');
    Route::get('/purchase-return/add/{id}', 'PurchaseReturnController@add');
    Route::resource('/purchase-return', 'PurchaseReturnController', ['except' => ['create']]);

    Route::get('/discount/activate/{id}', 'DiscountController@activate');
    Route::post('/discount/mass-deactivate', 'DiscountController@massDeactivate');
    Route::resource('discount', 'DiscountController');

    Route::group(['prefix' => 'account'], function () {
        Route::resource('/account', 'AccountController');
        Route::get('/fund-transfer/{id}', 'AccountController@getFundTransfer');
        Route::post('/fund-transfer', 'AccountController@postFundTransfer');
        Route::get('/deposit/{id}', 'AccountController@getDeposit');
        Route::post('/deposit', 'AccountController@postDeposit');
        Route::get('/close/{id}', 'AccountController@close');
        Route::get('/activate/{id}', 'AccountController@activate');
        Route::get('/delete-account-transaction/{id}', 'AccountController@destroyAccountTransaction');
        Route::get('/get-account-balance/{id}', 'AccountController@getAccountBalance');
        Route::get('/balance-sheet', 'AccountReportsController@balanceSheet');
        Route::get('/trial-balance', 'AccountReportsController@trialBalance');
        Route::get('/payment-account-report', 'AccountReportsController@paymentAccountReport');
        Route::get('/link-account/{id}', 'AccountReportsController@getLinkAccount');
        Route::post('/link-account', 'AccountReportsController@postLinkAccount');
        Route::get('/cash-flow', 'AccountController@cashFlow');
    });

    Route::resource('account-types', 'AccountTypeController');

    //Restaurant module
    Route::group(['prefix' => 'modules'], function () {
        Route::resource('tables', 'Restaurant\TableController');
        Route::resource('modifiers', 'Restaurant\ModifierSetsController');

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', 'Restaurant\ProductModifierSetController@edit');
        Route::get('/product-modifiers/{id}/duplicate', 'Restaurant\ProductModifierSetController@duplicate');
        Route::get('/modifiers-option', 'Restaurant\ModifierSetsController@getModifiers');
        Route::get('/get-ingredients{id}', 'Restaurant\ModifierSetsController@getIngredients');

        Route::post('/product-modifiers/{id}/update', 'Restaurant\ProductModifierSetController@update');
        Route::post('/product-modifiers/{id}/createCopy', 'Restaurant\ModifierSetsController@createCopy');
        Route::get('/product-modifiers/{id}/editIngredients', 'Restaurant\ModifierSetsController@editIngredients');
        Route::get('/product-modifiers/{id}/showIngredients', 'Restaurant\ModifierSetsController@showIngredients');

        Route::get('/product-modifiers/product-row/{product_id}', 'Restaurant\ProductModifierSetController@product_row');

        Route::get('/add-selected-modifiers', 'Restaurant\ProductModifierSetController@add_selected_modifiers');

        Route::get('/kitchen', 'Restaurant\KitchenController@index');
        Route::get('/kitchen/mark-as-cooked/{id}', 'Restaurant\KitchenController@markAsCooked');
        Route::post('/refresh-orders-list', 'Restaurant\KitchenController@refreshOrdersList');
        Route::post('/refresh-line-orders-list', 'Restaurant\KitchenController@refreshLineOrdersList');

        Route::get('/orders', 'Restaurant\OrderController@index');
        Route::get('/orders/mark-as-served/{id}', 'Restaurant\OrderController@markAsServed');
        Route::get('/data/get-pos-details', 'Restaurant\DataController@getPosDetails');
        Route::get('/orders/mark-line-order-as-served/{id}', 'Restaurant\OrderController@markLineOrderAsServed');
    });

    Route::get('bookings/get-todays-bookings', 'Restaurant\BookingController@getTodaysBookings');
    Route::resource('bookings', 'Restaurant\BookingController');

    Route::resource('types-of-service', 'TypesOfServiceController');
    Route::get('sells/edit-shipping/{id}', 'SellController@editShipping');
    Route::put('sells/update-shipping/{id}', 'SellController@updateShipping');
    Route::get('shipments', 'SellController@shipments');

    Route::post('upload-module', 'Install\ModulesController@uploadModule');

    Route::resource('manage-modules', 'Install\ModulesController')
        ->only(['index', 'destroy', 'update'])->parameters(['manage-modules' => 'module_name']);

    Route::resource('warranties', 'WarrantyController');

    Route::resource('dashboard-configurator', 'DashboardConfiguratorController')
        ->only(['edit', 'update']);

    //common controller for document & note
    Route::get('get-document-note-page', 'DocumentAndNoteController@getDocAndNoteIndexPage');
    Route::post('post-document-upload', 'DocumentAndNoteController@postMedia');
    Route::resource('note-documents', 'DocumentAndNoteController');

    //Kitchen Stations
    Route::get('kitchen-stations', 'KitchenStationController@index');
});

Route::middleware(['EcomApi', 'language'])->prefix('api/ecom')->group(function () {
    Route::get('products/{id?}', 'ProductController@getProductsApi');
    Route::get('categories', 'CategoryController@getCategoriesApi');
    Route::get('brands', 'BrandController@getBrandsApi');
    Route::post('customers', 'ContactController@postCustomersApi');
    Route::get('settings', 'BusinessController@getEcomSettings');
    Route::get('variations', 'ProductController@getVariationsApi');
    Route::post('orders', 'SellPosController@placeOrdersApi');
});

//common route
Route::middleware(['auth', 'language'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', 'HomeController@loadMoreNotifications');
    Route::get('/get-total-unread', 'HomeController@getTotalUnreadNotifications');
    Route::get('/purchases/print/{id}', 'PurchaseController@printInvoice');
    Route::get('/purchases/{id}', 'PurchaseController@show');
    Route::get('/sells/{id}', 'SellController@show');
    Route::get('/sells/{transaction_id}/print', 'SellPosController@printInvoice')->name('sell.printInvoice');
    Route::get('/sells/invoice-url/{id}', 'SellPosController@showInvoiceUrl');
    Route::get('/show-notification/{id}', 'HomeController@showNotification');
});

// API Routes
Route::group(['middleware' => ['setData', 'auth', 'SetSessionData', 'timezone', 'language']], function () {
    Route::get('/login-user', 'Api\LoginController@login');

    // Header Location DropDown API
    Route::get('/get-locations', 'Api\ProductController@getLocations');
    Route::get('/get-categories', 'Api\ProductController@getCategory');

    Route::get('/get-products', 'Api\ProductController@getProducts');
    Route::get('/get-product-row', 'Api\ProductController@getProductRow');

    // Header Button API
    Route::get('/get-suspended-sells', 'Api\ProductHeaderController@getSuspendedSells');
    Route::get('/edit-suspended-sell', 'Api\ProductHeaderController@editSuspendedSales');
    Route::post('/update-suspended-sell', 'Api\ProductHeaderController@updateSuspendedSales');
    Route::post('/delete-suspended-sell', 'Api\ProductHeaderController@deleteSuspendedSales');
    Route::post('/delete-all-suspended-sells', 'Api\ProductHeaderController@deleteAllSuspendedSales');
    Route::get('/cash-register-details', 'Api\ProductHeaderController@getRegisterDetails');
    Route::get('/get-cash-close-register/{id?}', 'Api\ProductHeaderController@getCloseRegister');
    Route::post('/cash-close-register', 'Api\ProductHeaderController@closeRegister');

    // Get Table
    Route::get('/get-tables', 'Api\ProductDetailsController@getTables');
    // Get Service Staff
    Route::get('/get-service-staffs', 'Api\ProductDetailsController@getServiceStaffs');
    // Get TypesOfService
    Route::get('/get-types-of-services', 'Api\ProductDetailsController@getTypesOfServices');
    Route::get('/get-types-of-service-details', 'Api\ProductDetailsController@getTypesOfServiceDetails');

    // Customer
    Route::post('/store-customer', 'Api\ProductDetailsController@storeCustomer');
    Route::get('/search-customer', 'Api\ProductDetailsController@searchCustomer');

    // Footer Button API
    Route::post('/suspended-sell-note', 'Api\ProductDetailsController@suspendedSellNote');
    Route::get('/get-recent-transactions', 'Api\ProductDetailsController@getRecentTransactions');
    Route::post('/recent-transaction-delete', 'Api\ProductDetailsController@recentTransactionDelete');
    Route::get('/recent-transaction-print', 'Api\ProductDetailsController@recentTransactionPrint');
    Route::post('/product-draft', 'Api\ProductDetailsController@productDraft');
    Route::post('/finalize-payment', 'Api\ProductDetailsController@finalizePayment');
    Route::post('/update-finalize-payment', 'Api\ProductHeaderController@updateFinalizePayment');

    // Check Internet Connection
    Route::get('/check-internet-connection', 'Api\ProductController@checkInternetConnection');
});
