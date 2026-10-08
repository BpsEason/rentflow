<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 只有API請求才套用統一的錯誤回應格式
        $exceptions->render(function (Throwable $e, $request) {
            if (!$request->is('api/*')) {
                // 非API請求（Filament、Web）使用預設處理
                return null;
            }

            // 401 - 未認證
            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('未授權存取', null, [], 401);
            }

            // 403 - 權限不足
            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('權限不足', null, [], 403);
            }

            // 404 - 資源不存在
            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error('資源不存在', null, [], 404);
            }

            // 422 - 驗證錯誤
            if ($e instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => '驗證失敗',
                    'errors' => $e->errors(),
                ], 422);
            }

            // 其他HTTP例外
            if ($e instanceof HttpException) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'HTTP錯誤',
                    null,
                    [],
                    $e->getStatusCode()
                );
            }

            // 500 - 伺服器內部錯誤
            if (app()->environment('production')) {
                return ApiResponse::error('伺服器內部錯誤', null, [], 500);
            }

            // 開發環境顯示詳細錯誤資訊
            return ApiResponse::error(
                $e->getMessage() ?: '伺服器內部錯誤',
                null,
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                500
            );
        });
    })->create();
