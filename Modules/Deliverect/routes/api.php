<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Deliverect\App\Http\Controllers\DeliverectController;
use Modules\Deliverect\App\Services\RegisterPos;


// Welcome to Fastich Deliverect Module
Route::get('/deliverect', function () {
    return response()->json(['message' => 'Welcome to Fastich Deliverect Module']);
});

Route::post('/deliverect/webhooks/register-pos', [DeliverectController::class, 'registerPos']);


Route::get('/deliverect/send-notification', [DeliverectController::class, 'sendNotification']);


Route::prefix('/deliverect/{business_id}')->group(function () {

    Route::get('/info', function ($business_id) {
        return response()->json(['message' => 'Welcome to Fastich Deliverect Module the bussiness ' . $business_id]);
    });
    // Deliverect Status APIS
    Route::get('/on-board-with-deliverect/status', [DeliverectController::class, 'onBoardWithDeliverectStatus']);
    Route::post('/on-board-with-deliverect', [DeliverectController::class, 'onBoardWithDeliverect']);
    Route::get('/sync-channels', [DeliverectController::class, 'syncChannels']);



    Route::prefix("/{pos_location}")->group(function () {
        Route::get('/get-products', [DeliverectController::class, 'getApiProducts']);
        Route::post('/get-products', [DeliverectController::class, 'getApiProducts']);


        Route::get('/get-registration-url', [DeliverectController::class, 'getRegistrationUrl']);
        Route::post('/map-deliverect-location', [DeliverectController::class, 'mapDeliverectLocation']);
        Route::get('/get-location-mapping', [DeliverectController::class, 'getDeliverectLocation']);
        // Registration
        Route::get('/get-registration-url', [DeliverectController::class, 'getRegistrationUrl']);
        Route::get('/registration-status', [DeliverectController::class, 'registerationStatus']);
        Route::post('/check-onesignal-subscription', [DeliverectController::class, 'checkOneSignalSubscription']);

        Route::get('/brands', [DeliverectController::class, 'getBrands']);
        Route::get('/allergens_tags', [DeliverectController::class, 'getAllergensAndTags']);
        Route::get('/categories', [DeliverectController::class, 'getCategories']);
        Route::get('/channels', [DeliverectController::class, 'getChannels']);
        Route::post('/order/preparation-time', [DeliverectController::class, 'updateOrderPreparation']);
        Route::put('/order/order-status', [DeliverectController::class, 'updateOrderStatus']);
        Route::post('/sync-products', [DeliverectController::class, 'internalSyncProducts'])->name('deliverect.internal-sync-products');

        // // // // Webhooks // // // // 
        Route::get('/webhooks/sync-products', [DeliverectController::class, 'syncProducts']);
        Route::post('/webhooks/order-notification', [DeliverectController::class, 'orderNotification']);



        Route::post('/recived-by-pos-order', [DeliverectController::class, 'recivedByPosOrder']);
        Route::post('/on-accpet-deliverect-order', [DeliverectController::class, 'onAcceptNewOrder']);
        Route::post('/on-reject-deliverect-order', [DeliverectController::class, 'onRejectOrder']);
        Route::post('/on-ready-deliverect-order', [DeliverectController::class, 'onReadyOrder']);
        Route::post('/on-finalized-deliverect-order', [DeliverectController::class, 'onFinalizedOrder']);

        Route::post('/get-all-new-orders', [DeliverectController::class, 'getAllNewOrders']);
        Route::post('/get-deliverect-order', [DeliverectController::class, 'getDeliverectOrderDetail']);

    });
});
