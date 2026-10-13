<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerReservationsController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ReservationsController;

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

// API v1 路由 - 僅保留公開與會員功能
Route::prefix('v1')->group(function () {
    // 公開可存取的路由（不需要登入）- 包含租戶slug作為路徑參數
    Route::prefix('public/{tenantSlug}')
        ->middleware(\App\Http\Middleware\ResolvePublicTenant::class)
        ->where(['tenantSlug' => '[a-zA-Z0-9-]+'])
        ->group(function () {
            // 公開車輛列表與詳情
            Route::get('/vehicles', [VehicleController::class, 'publicIndex']);
            // 車輛可用性查詢 - 必須放在有{id}參數的路由之前，避免路由匹配衝突
            Route::get('/vehicles/available', [VehicleController::class, 'available']);
            // 租金試算
            Route::post('/vehicles/{id}/calculate-price', [VehicleController::class, 'calculatePrice']);
            Route::get('/vehicles/{id}', [VehicleController::class, 'publicShow']);
            // 訪客預約端點
            Route::post('/reservations', [ReservationsController::class, 'guestStore']);
        });

    // 前台會員專用API路由 - 必須包含租戶slug才能解析租戶上下文
    Route::prefix('{tenantSlug}/customer')->where(['tenantSlug' => '[a-zA-Z0-9-]+'])->middleware(\App\Http\Middleware\ResolvePublicTenant::class)->group(function () {
        // 會員認證路由
        Route::prefix('auth')->group(function () {
            Route::post('/register', [CustomerAuthController::class, 'register']);
            Route::post('/login', [CustomerAuthController::class, 'login']);
            Route::post('/refresh', [CustomerAuthController::class, 'refresh'])->middleware('auth:api');
            Route::post('/logout', [CustomerAuthController::class, 'logout'])->middleware('auth:api');
            Route::get('/me', [CustomerAuthController::class, 'me'])->middleware('auth:api');
            Route::post('/forgot-password', [CustomerAuthController::class, 'forgotPassword']);
            Route::post('/reset-password', [CustomerAuthController::class, 'resetPassword']);
        });

        // 需要認證的會員API
        Route::middleware('auth:api')->group(function () {
            // 會員資料更新
            Route::post('/profile', [CustomerAuthController::class, 'updateProfile']);

            // 會員預約相關API
            Route::prefix('reservations')->group(function () {
                Route::get('/', [CustomerReservationsController::class, 'index']);
                Route::get('/{id}', [CustomerReservationsController::class, 'show']);
                Route::post('/', [CustomerReservationsController::class, 'store']);
                Route::post('/{id}/cancel', [CustomerReservationsController::class, 'cancel']);
            });
        });
    });
});
