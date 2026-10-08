<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * 最小的API回應輔助類別
 * 唯一職責：格式化統一的API回應結構
 * 不新增大型抽象，只提供最基本的success/error/paginate方法
 */
class ApiResponse
{
    /**
     * 成功回應
     */
    public static function success(mixed $data = null, string $message = '操作成功', array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    /**
     * 失敗回應
     */
    public static function error(string $message = '操作失敗', mixed $data = null, array $meta = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    /**
     * 分頁資料回應
     * 自動處理Laravel的LengthAwarePaginator，提取分頁資訊到meta
     */
    public static function paginate(mixed $data, string $message = '取得資料成功'): JsonResponse
    {
        $meta = [];

        // 如果是Laravel的分頁器，提取分頁資訊
        if (method_exists($data, 'currentPage')) {
            $meta = [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
            $data = $data->items();
        }

        return static::success($data, $message, $meta);
    }
}
