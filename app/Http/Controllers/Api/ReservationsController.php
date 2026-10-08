<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\CreateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use App\Application\Reservations\UseCases\CreateReservation;
use App\Application\Reservations\UseCases\ConfirmReservation;
use App\Application\Reservations\UseCases\CancelReservation;
use App\Domain\Reservations\DTO\CreateReservationData;
use App\Domain\Reservations\Repositories\ReservationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Reservations", description="預約管理相關API")
 */
class ReservationsController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
        // 中間件：認證 + 解析租戶
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
     *     path="/api/v1/reservations",
     *     summary="取得所有預約",
     *     description="取得目前租戶的所有預約列表",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得預約列表",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservations = $reservationRepository->getAllForTenant($tenantId);

        return ApiResponse::success(
            ReservationResource::collection($reservations),
            '取得預約列表成功'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reservations/{reservation}",
     *     summary="取得單一預約詳情",
     *     description="取得指定預約的詳細資訊",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         description="預約ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得預約詳情",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="預約不存在",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show($reservationId, ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservation = $reservationRepository->findForTenant($reservationId, $tenantId);

        if (!$reservation) {
            return ApiResponse::error('預約不存在', null, [], 404);
        }

        return ApiResponse::success(
            new ReservationResource($reservation),
            '取得預約成功'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reservations",
     *     summary="創建新預約",
     *     description="建立一個新的車輛預約",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vehicle_id","customer_id","start_date","end_date"},
     *             @OA\Property(property="vehicle_id", type="integer", example=1),
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2024-01-05T10:00:00Z"),
     *             @OA\Property(property="notes", type="string", example="需要兒童座椅")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="預約創建成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="車輛時間衝突",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="驗證錯誤",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function store(CreateReservationRequest $request, CreateReservation $useCase): JsonResponse
    {
        try {
            $validated = $request->validated();

            // 使用新的DTO結構
            $data = CreateReservationData::fromArray($validated);

            $reservation = $useCase->execute($data);
            $reservation->load(['customer', 'vehicle']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '預約建立成功',
                [],
                201
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reservations/{reservation}/confirm",
     *     summary="確認預約",
     *     description="將待確認的預約標記為已確認",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         description="預約ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="預約確認成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="預約狀態無法確認",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function confirm($reservationId, ConfirmReservation $useCase): JsonResponse
    {
        try {
            $reservation = $useCase->execute($reservationId);
            $reservation->load(['customer', 'vehicle']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '預約確認成功'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reservations/{reservation}/cancel",
     *     summary="取消預約",
     *     description="取消現有預約",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         description="預約ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", example="客戶取消行程")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="預約取消成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="預約狀態無法取消",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function cancel($reservationId, CancelReservation $useCase, Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $reservation = $useCase->execute($reservationId, $reason);
            $reservation->load(['customer', 'vehicle']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '預約取消成功'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reservations/{reservation}/pickup",
     *     summary="辦理取車",
     *     description="將已確認的預約標記為已取車",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="reservation", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="取車成功"),
     *     @OA\Response(response=409, description="預約狀態無效")
     * )
     */
    public function pickup($reservationId, ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservation = $reservationRepository->findForTenant($reservationId, $tenantId);

        if (!$reservation) {
            return ApiResponse::error('預約不存在', null, [], 404);
        }

        try {
            $reservation->pickUp();
            $reservation->load(['customer', 'vehicle']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '辦理取車成功'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reservations/{reservation}/return",
     *     summary="辦理歸還",
     *     description="將已取車的預約標記為已歸還",
     *     tags={"Reservations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="reservation", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="歸還成功"),
     *     @OA\Response(response=409, description="預約狀態無效")
     * )
     */
    public function return($reservationId, ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservation = $reservationRepository->findForTenant($reservationId, $tenantId);

        if (!$reservation) {
            return ApiResponse::error('預約不存在', null, [], 404);
        }

        try {
            $reservation->return();
            $reservation->load(['customer', 'vehicle']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '辦理歸還成功'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * 統一異常處理
     */
    private function handleException(\Throwable $e): JsonResponse
    {
        $code = (is_int($e->getCode()) || is_numeric($e->getCode())) && (int)$e->getCode() >= 100 && (int)$e->getCode() <= 599
            ? (int)$e->getCode()
            : 400;

        return ApiResponse::error($e->getMessage(), null, [], $code);
    }
}
