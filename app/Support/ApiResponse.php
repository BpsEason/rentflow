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
        $paginator = null;

        // 處理ResourceCollection包裹的分頁器
        if ($data instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection && $data->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $paginator = $data->resource;
        }
        // 直接傳入LengthAwarePaginator的情況
        elseif ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $paginator = $data;
        }

        if ($paginator) {
            $meta = [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ];
            $data = $paginator->items();
        }

        return static::success($data, $message, $meta);
    }
}
