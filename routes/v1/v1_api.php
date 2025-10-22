<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\FloorController;
use App\Http\Controllers\API\LandlordPaymentController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\TelegramController;
use App\Http\Controllers\API\TenantController;
use App\Http\Controllers\API\TenantPaymentController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);

    Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post("/profile/update", [AuthController::class, "updateProfile"]);
        Route::post('/telegram/connect', [TelegramController::class, 'generate']);
        Route::post('/telegram/tenant/connect/{id}', [TelegramController::class, 'generateTenantLink']);
        Route::apiResource('/properties', PropertyController::class);
        Route::apiResource('/tenants', TenantController::class);
        Route::apiResource('/floors', FloorController::class);
        Route::apiResource('/rooms', RoomController::class);
        Route::post('/rooms/assign', [RoomController::class, 'assign']);
        Route::post('/create-payment', [PaymentController::class, 'store']);
        Route::put('/payments/{id}', [PaymentController::class, 'update']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::post('/payments/{id}/mark-paid', [PaymentController::class, 'markPaid']);
        Route::put('/payments/{id}/reject', [PaymentController::class, 'rejectPayment']);
        Route::get("/payments", [PaymentController::class, 'index']);
        Route::get('/payment-methods', [LandlordPaymentController::class, 'index']);
        Route::post('/payment-methods', [LandlordPaymentController::class, 'saveSettings']);
        Route::get('/payment-method-types', [LandlordPaymentController::class, 'getPaymentMethodTypes']);
        Route::get('dashboard/stats', [DashboardController::class, 'getDashboardStats']);
        //list

        Route::get("/t-list", [PropertyController::class, "listTenant"]);
    });
    Route::prefix('tenant')->group(function () {
    
    Route::get('payments/{payment}/show-method/{method}', [TenantPaymentController::class, 'showPaymentMethod'])
         ->name('tenant.payments.show_method');
    
    Route::post('payments/{payment}/submit', [TenantPaymentController::class, 'submitPayment'])
         ->name('tenant.payments.submit');
});
});

Route::post('/send', [AuthController::class, "sendMessage"]);
