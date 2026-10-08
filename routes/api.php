<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehiclePricingController;
use App\Http\Controllers\Api\ReservationsController;
use App\Http\Controllers\Api\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 路由
Route::prefix('v1')->group(function () {
    // 認證路由
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');
    });

    // 需要認證的路由
    Route::middleware('auth:api')->group(function () {
        // 客戶相關 API
        Route::apiResource('customers', CustomerController::class);

        // 車輛相關 API
        Route::apiResource('vehicles', VehicleController::class);

        // 車輛定價相關 API
        Route::apiResource('vehicle-pricings', VehiclePricingController::class);

        // 預約相關 API
        Route::prefix('reservations')->group(function () {
            Route::post('/', [ReservationsController::class, 'store']);
            Route::get('/', [ReservationsController::class, 'index']);
            Route::get('/{reservation}', [ReservationsController::class, 'show']);
            Route::post('/{reservation}/confirm', [ReservationsController::class, 'confirm']);
            Route::post('/{reservation}/cancel', [ReservationsController::class, 'cancel']);
            Route::post('/{reservation}/pickup', [ReservationsController::class, 'pickup']);
            Route::post('/{reservation}/return', [ReservationsController::class, 'return']);
        });

        // 訂單相關 API
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{order}', [OrderController::class, 'show']);
            Route::post('/{order}/confirm', [OrderController::class, 'confirm']);
            Route::post('/{order}/complete', [OrderController::class, 'complete']);
            Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
        });
    });
});
