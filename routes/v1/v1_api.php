<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FloorController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\TelegramController;
use App\Http\Controllers\API\TenantController;
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);

    Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/telegram/connect', [TelegramController::class, 'generate']);
        Route::apiResource('/properties', PropertyController::class);
        Route::apiResource('/tenants', TenantController::class);
        Route::apiResource('/floors', FloorController::class);
        Route::apiResource('/rooms', RoomController::class);
        // Route::post('/tenants/{id}/assign-room'. [TenantController::class, 'assignRoom']);         
    });
});
