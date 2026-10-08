<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Orders\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Reservation;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(name="Orders", description="訂單管理與 Lifecycle 相關API")
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
        $this->middleware('auth:api');
        $this->middleware(function ($request, $next) {
            $tenant = $this->tenantContext->getCurrentTenant();

            if (!$tenant) {
                return ApiResponse::error('無法解析租戶資訊，使用者未關聯任何租戶', null, [], 403);
            }

            return $next($request);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     summary="取得訂單列表",
     *     description="取得目前租戶的所有訂單列表",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得訂單列表",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $orders = Order::where('tenant_id', $tenantId)->with('reservation')->paginate(15);

        return ApiResponse::paginate(
            OrderResource::collection($orders),
            '取得訂單列表成功'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     summary="建立訂單",
     *     description="從現有已確認或已取車之預約建立訂單",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reservation_id"},
     *             @OA\Property(property="reservation_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="建立訂單成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(response=409, description="預約狀態無效或訂單已存在")
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservationId = $request->input('reservation_id');

        $reservation = Reservation::where('tenant_id', $tenantId)->where('id', $reservationId)->first();

        if (!$reservation) {
            return ApiResponse::error('預約不存在或不屬於當前租戶', null, [], 404);
        }

        try {
            $order = Order::createFromReservation($reservation);
            $order->load('reservation');

            return ApiResponse::success(
                new OrderResource($order),
                '建立訂單成功',
                [],
                201
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, [], 409);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{order}",
     *     summary="取得單一訂單詳情",
     *     description="取得指定訂單詳情",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得訂單詳情",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(response=404, description="訂單不存在")
     * )
     */
    public function show($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $order = Order::where('tenant_id', $tenantId)->with('reservation')->where('id', $id)->first();

        if (!$order) {
            return ApiResponse::error('訂單不存在', null, [], 404);
        }

        return ApiResponse::success(
            new OrderResource($order),
            '取得訂單詳情成功'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{order}/confirm",
     *     summary="確認訂單",
     *     description="將待處理狀態的訂單轉為已確認",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="確認訂單成功"),
     *     @OA\Response(response=409, description="訂單狀態無法確認")
     * )
     */
    public function confirm($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $order = Order::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$order) {
            return ApiResponse::error('訂單不存在', null, [], 404);
        }

        try {
            $order->confirm();
            $order->load('reservation');

            return ApiResponse::success(
                new OrderResource($order),
                '確認訂單成功'
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, [], 409);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{order}/complete",
     *     summary="完成訂單",
     *     description="將已確認狀態的訂單轉為完成",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="完成訂單成功"),
     *     @OA\Response(response=409, description="訂單狀態無法完成")
     * )
     */
    public function complete($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $order = Order::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$order) {
            return ApiResponse::error('訂單不存在', null, [], 404);
        }

        try {
            $order->complete();
            $order->load('reservation');

            return ApiResponse::success(
                new OrderResource($order),
                '完成訂單成功'
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, [], 409);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{order}/cancel",
     *     summary="取消訂單",
     *     description="將訂單轉為已取消",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="取消訂單成功"),
     *     @OA\Response(response=409, description="訂單狀態無法取消")
     * )
     */
    public function cancel($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $order = Order::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$order) {
            return ApiResponse::error('訂單不存在', null, [], 404);
        }

        try {
            $order->cancel();
            $order->load('reservation');

            return ApiResponse::success(
                new OrderResource($order),
                '取消訂單成功'
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, [], 409);
        }
    }
}
