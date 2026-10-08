<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VehiclePricings\StoreVehiclePricingRequest;
use App\Http\Requests\Api\VehiclePricings\UpdateVehiclePricingRequest;
use App\Http\Resources\VehiclePricingResource;
use App\Models\Vehicle;
use App\Models\VehiclePricing;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(name="Vehicle Pricings", description="車輛定價管理相關API")
 */
class VehiclePricingController extends Controller
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
     *     path="/api/v1/vehicle-pricings",
     *     summary="取得車輛定價列表",
     *     description="取得目前租戶的所有車輛定價列表",
     *     tags={"Vehicle Pricings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得車輛定價列表",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $pricings = VehiclePricing::where('tenant_id', $tenantId)->paginate(15);

        return ApiResponse::paginate(
            VehiclePricingResource::collection($pricings),
            '取得車輛定價列表成功'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vehicle-pricings",
     *     summary="建立車輛定價",
     *     description="建立車輛定價設定",
     *     tags={"Vehicle Pricings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vehicle_id","weekday_price","weekend_price","holiday_price"},
     *             @OA\Property(property="vehicle_id", type="integer", example=1),
     *             @OA\Property(property="weekday_price", type="number", format="float", example=1500.00),
     *             @OA\Property(property="weekend_price", type="number", format="float", example=2000.00),
     *             @OA\Property(property="holiday_price", type="number", format="float", example=2500.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="建立車輛定價成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     )
     * )
     */
    public function store(StoreVehiclePricingRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $validated = $request->validated();

        $vehicle = Vehicle::where('tenant_id', $tenantId)->where('id', $validated['vehicle_id'])->first();
        if (!$vehicle) {
            return ApiResponse::error('該車輛不存在或不屬於當前租戶', null, [], 404);
        }

        if (VehiclePricing::where('tenant_id', $tenantId)->where('vehicle_id', $validated['vehicle_id'])->exists()) {
            return ApiResponse::error('該車輛已設定過價格，請使用更新功能', null, [], 409);
        }

        $validated['tenant_id'] = $tenantId;
        $pricing = VehiclePricing::create($validated);

        return ApiResponse::success(
            new VehiclePricingResource($pricing),
            '建立車輛定價成功',
            [],
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vehicle-pricings/{vehiclePricing}",
     *     summary="取得單一車輛定價詳情",
     *     description="取得指定車輛定價詳情",
     *     tags={"Vehicle Pricings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vehiclePricing", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得車輛定價詳情",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(response=404, description="車輛定價不存在")
     * )
     */
    public function show($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $pricing = VehiclePricing::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$pricing) {
            return ApiResponse::error('車輛定價不存在', null, [], 404);
        }

        return ApiResponse::success(
            new VehiclePricingResource($pricing),
            '取得車輛定價詳情成功'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/vehicle-pricings/{vehiclePricing}",
     *     summary="更新車輛定價資訊",
     *     description="更新指定車輛定價資訊",
     *     tags={"Vehicle Pricings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vehiclePricing", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="更新車輛定價成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(response=404, description="車輛定價不存在")
     * )
     */
    public function update(UpdateVehiclePricingRequest $request, $id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $pricing = VehiclePricing::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$pricing) {
            return ApiResponse::error('車輛定價不存在', null, [], 404);
        }

        $pricing->update($request->validated());

        return ApiResponse::success(
            new VehiclePricingResource($pricing),
            '更新車輛定價成功'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/vehicle-pricings/{vehiclePricing}",
     *     summary="刪除車輛定價",
     *     description="刪除指定車輛定價",
     *     tags={"Vehicle Pricings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vehiclePricing", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="刪除車輛定價成功"),
     *     @OA\Response(response=404, description="車輛定價不存在")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $pricing = VehiclePricing::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$pricing) {
            return ApiResponse::error('車輛定價不存在', null, [], 404);
        }

        $pricing->delete();

        return ApiResponse::success(null, '刪除車輛定價成功');
    }
}
