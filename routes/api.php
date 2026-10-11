<?php

use App\Http\Controllers\Api\Zatca\ZatcaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout-app-user', 'App\Http\Controllers\Api\ApiLoginController@logout');
});

Route::group(['middleware' => ['cors', 'json.response']], function () {
    // public routes
    Route::post('/login-app-user', 'App\Http\Controllers\Api\ApiLoginController@login');
    Route::get('/logout-app-user', 'App\Http\Controllers\Api\ApiLoginController@logout');
    Route::post('/register', 'App\Http\Controllers\Auth\ApiAuthController@register');
    Route::post('/user-profile-app-user', 'App\Http\Controllers\Api\ApiUserController@getProfile');
    Route::post('/user-locations-app-user', 'App\Http\Controllers\Api\ApiUserController@getApiLocations');
    Route::post('/invoice-print-app-user', 'App\Http\Controllers\Api\ApiUserController@getInvoicePrint');
    Route::post('/invoice-layout-app-user', 'App\Http\Controllers\Api\ApiUserController@getInvoiceLayout');
    Route::post('/finalize-payment-app-user', 'App\Http\Controllers\Api\ApiUserController@finalizePaymentApi');
    Route::post('/update-payment-app-user', 'App\Http\Controllers\Api\ApiUserController@updateFinalizePaymentApi');
    Route::post('/get-cash-register-app-user', 'App\Http\Controllers\Api\ApiUserController@createCashRegister');
    Route::post('/create-cash-register-app-user', 'App\Http\Controllers\Api\ApiUserController@openRegisterAppUser');
    Route::post('/get-cash-register-details-app-user', 'App\Http\Controllers\Api\ApiUserController@getRegisterDetails');
    Route::post('/get-recent-transactions-app-user', 'App\Http\Controllers\Api\ApiUserController@getRecentTransactions');
    Route::post('/cash-close-register-app-user', 'App\Http\Controllers\Api\ApiUserController@closeRegister');
    Route::post('/seach-customer-app-user', 'App\Http\Controllers\Api\ApiUserController@searchCustomer');
    Route::post('/store-customer-app-user', 'App\Http\Controllers\Api\ApiUserController@storeCustomer');
    Route::post('/get-categories-app-user', 'App\Http\Controllers\Api\ApiProductController@getCategory');
    Route::post('/get-products-app-user', 'App\Http\Controllers\Api\ApiProductController@getProducts');
    Route::post('/get-product-row-app-user', 'App\Http\Controllers\Api\ApiProductController@getProductRow');
    Route::post('/on-hold-print-app-user', 'App\Http\Controllers\Api\ApiProductController@suspendedSellNote');
    Route::post('/get-suspended-sells-app-user', 'App\Http\Controllers\Api\ApiProductController@getSuspendedSells');
    Route::post('/on-hold-update-suspended-sell-app-user', 'App\Http\Controllers\Api\ApiProductController@updateSuspendedSales');
    Route::post('/edit-suspended-sell-app-user', 'App\Http\Controllers\Api\ApiProductController@editSuspendedSales');
    Route::post('/delete-suspended-sell-app-user', 'App\Http\Controllers\Api\ApiProductController@deleteSuspendedSales');
    Route::post('/delete-all-suspended-sells-app-user', 'App\Http\Controllers\Api\ApiProductController@deleteAllSuspendedSales');
    Route::post('/recent-transaction-deleteapp-user', 'App\Http\Controllers\Api\ApiProductController@recentTransactionDelete');
    Route::post('/return-on-hold-sale-app-user', 'App\Http\Controllers\Api\ApiProductController@onHoldTransactionReturn');
    Route::post('/return-sale-app-user', 'App\Http\Controllers\Api\ApiProductController@recentTransactionReturn');
    Route::post('/get-tables-app-user', 'App\Http\Controllers\Api\ApiProductController@getTables');
    Route::post('/get-service-staffs-app-user', 'App\Http\Controllers\Api\ApiProductController@getServiceStaffs');
    Route::get('/app-upgradation-app-user', 'App\Http\Controllers\Api\ApiUserController@getAppUpgeadation');
    Route::get('/show-paths', function () {
        // Fetch the paths
        $storagePath = storage_path();
        $appPath     = app_path();

        // Return the paths as a JSON response
        return response()->json([
            'storage_path' => $storagePath,
            'app_path'     => $appPath,
        ]);
    });

    Route::post("/zatca/on-board-egs", 'App\Http\Controllers\Api\Zatca\ZatcaApiController@onBoardEgs');
    Route::post("/zatca/report-invoice", 'App\Http\Controllers\Api\Zatca\ZatcaApiController@reportInvoice');
    Route::post('/zatca/renew-certificate', [ZatcaApiController::class, 'renewProductionCertificate']);
    Route::get('/path-info', function () {
        return response()->json([
            'base_path'     => base_path(),
            'app_path'      => app_path(),
            'config_path'   => config_path(),
            'database_path' => database_path(),
            'public_path'   => public_path(),
            'resource_path' => resource_path(),
            'storage_path'  => storage_path(),
        ]);
    });
});
