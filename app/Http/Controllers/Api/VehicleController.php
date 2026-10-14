<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\Api\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Api\Vehicles\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Models\Tenant;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

#[OA\Tag(name: "Vehicles", description: "車輛管理相關API")]
class VehicleController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
        // 只有需要認證的方法才套用auth中介層
        $this->middleware('auth:api')->except(['publicIndex', 'publicShow', 'available', 'calculatePrice']);
        $this->middleware(function ($request, $next) {
            // 公開端點不需要檢查租戶上下文，它們會自己處理租戶邏輯
            if (in_array($request->route()->getActionMethod(), ['publicIndex', 'publicShow', 'available', 'calculatePrice'])) {
                return $next($request);
            }

            $tenant = $this->tenantContext->getCurrentTenant();

            if (!$tenant) {
                return ApiResponse::error('無法解析租戶資訊，使用者未關聯任何租戶', null, [], 403);
            }

            return $next($request);
        });
    }

    #[OA\Get(
        path: "/api/v1/vehicles",
        summary: "取得車輛列表",
        description: "取得目前租戶的所有車輛列表",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "成功取得車輛列表", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse"))
        ]
    )]
    public function index(): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $vehicles = Vehicle::where('tenant_id', $tenantId)->with('pricing')->paginate(15);

        return ApiResponse::paginate(
            VehicleResource::collection($vehicles),
            '取得車輛列表成功'
        );
    }

    #[OA\Post(
        path: "/api/v1/vehicles",
        summary: "建立車輛",
        description: "建立新車輛",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "plate_number"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Toyota Altis"),
                    new OA\Property(property: "plate_number", type: "string", example: "ABC-1234"),
                    new OA\Property(property: "status", type: "string", example: "AVAILABLE"),
                    new OA\Property(property: "seats", type: "integer", example: 5),
                    new OA\Property(property: "description", type: "string", example: "舒適轎車")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "建立車輛成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse"))
        ]
    )]
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $validated = $request->validated();
        $validated['tenant_id'] = $tenantId;

        if (Vehicle::where('tenant_id', $tenantId)->where('plate_number', $validated['plate_number'])->exists()) {
            return ApiResponse::error('車牌號碼在此租戶內已存在', null, [], 409);
        }

        $vehicle = Vehicle::create($validated);

        return ApiResponse::success(
            new VehicleResource($vehicle),
            '建立車輛成功',
            [],
            201
        );
    }

    #[OA\Get(
        path: "/api/v1/vehicles/{vehicle}",
        summary: "取得單一車輛詳情",
        description: "取得指定車輛詳情",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "vehicle", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得車輛詳情", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function show($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $vehicle = Vehicle::where('tenant_id', $tenantId)->with('pricing')->where('id', $id)->first();

        if (!$vehicle) {
            return ApiResponse::error('車輛不存在', null, [], 404);
        }

        return ApiResponse::success(
            new VehicleResource($vehicle),
            '取得車輛詳情成功'
        );
    }

    #[OA\Put(
        path: "/api/v1/vehicles/{vehicle}",
        summary: "更新車輛資訊",
        description: "更新指定車輛資訊",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "vehicle", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "更新車輛成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    #[OA\Patch(
        path: "/api/v1/vehicles/{vehicle}",
        summary: "部分更新車輛資訊",
        description: "部分更新指定車輛資訊",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "vehicle", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "更新車輛成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function update(UpdateVehicleRequest $request, $id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $vehicle = Vehicle::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$vehicle) {
            return ApiResponse::error('車輛不存在', null, [], 404);
        }

        $validated = $request->validated();
        if (isset($validated['plate_number']) && $validated['plate_number'] !== $vehicle->plate_number) {
            if (Vehicle::where('tenant_id', $tenantId)->where('plate_number', $validated['plate_number'])->where('id', '!=', $id)->exists()) {
                return ApiResponse::error('車牌號碼在此租戶內已存在', null, [], 409);
            }
        }

        $vehicle->update($validated);
        $vehicle->load('pricing');

        return ApiResponse::success(
            new VehicleResource($vehicle),
            '更新車輛成功'
        );
    }

    #[OA\Delete(
        path: "/api/v1/vehicles/{vehicle}",
        summary: "刪除車輛",
        description: "刪除指定車輛",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "vehicle", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "刪除車輛成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function destroy($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $vehicle = Vehicle::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$vehicle) {
            return ApiResponse::error('車輛不存在', null, [], 404);
        }

        $vehicle->delete();

        return ApiResponse::success(null, '刪除車輛成功');
    }

    /**
     * 公開取得車輛列表（不需要登入）
     */
    #[OA\Get(
        path: "/api/v1/public/vehicles",
        summary: "公開取得車輛列表",
        description: "取得目前租戶的所有可租用車輛列表，供前台公開存取",
        tags: ["Vehicles"],
        parameters: [
            new OA\Parameter(name: "X-Tenant-Slug", in: "header", required: true, schema: new OA\Schema(type: "string"), description: "租戶唯一識別碼")
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得車輛列表", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 403, description: "無法解析租戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function publicIndex(): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
        }

        // 只返回可用的車輛，包含定價資訊
        $vehicles = Vehicle::where('tenant_id', $tenant->id)
            ->where('status', 'AVAILABLE')
            ->with('pricing')
            ->paginate(15);

        return ApiResponse::paginate(
            VehicleResource::collection($vehicles),
            '取得車輛列表成功'
        );
    }

    /**
     * 公開取得單一車輛詳情（不需要登入）
     */
    #[OA\Get(
        path: "/api/v1/public/vehicles/{id}",
        summary: "公開取得車輛詳情",
        description: "取得指定車輛詳情，供前台公開存取",
        tags: ["Vehicles"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "X-Tenant-Slug", in: "header", required: true, schema: new OA\Schema(type: "string"), description: "租戶唯一識別碼")
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得車輛詳情", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 403, description: "無法解析租戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function publicShow($tenantSlug, $id): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
        }

        $vehicle = Vehicle::where('tenant_id', $tenant->id)
            ->with('pricing')
            ->where('id', $id)
            ->first();

        if (!$vehicle) {
            return ApiResponse::error('車輛不存在', null, [], 404);
        }

        return ApiResponse::success(
            new VehicleResource($vehicle),
            '取得車輛詳情成功'
        );
    }

    /**
     * 查詢指定租期內可用的車輛
     */
    #[OA\Get(
        path: "/api/v1/public/vehicles/available",
        summary: "查詢指定租期內可用的車輛",
        description: "根據開始和結束時間查詢當前租戶內可以預約的車輛",
        tags: ["Vehicles"],
        parameters: [
            new OA\Parameter(name: "X-Tenant-Slug", in: "header", required: true, schema: new OA\Schema(type: "string"), description: "租戶唯一識別碼"),
            new OA\Parameter(name: "pickup_branch_id", in: "query", required: true, schema: new OA\Schema(type: "integer"), description: "取車據點ID"),
            new OA\Parameter(name: "start_at", in: "query", required: true, schema: new OA\Schema(type: "string", format: "date-time"), description: "開始時間"),
            new OA\Parameter(name: "end_at", in: "query", required: true, schema: new OA\Schema(type: "string", format: "date-time"), description: "結束時間")
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得可用車輛列表", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 403, description: "無法解析租戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 422, description: "參數驗證失敗", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function available($tenantSlug, \Illuminate\Http\Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
        }

        // 驗證請求參數
        $validated = $request->validate([
            'pickup_branch_id' => 'required|integer|exists:branches,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $startAt = \Carbon\Carbon::parse($validated['start_at']);
        $endAt = \Carbon\Carbon::parse($validated['end_at']);
        $pickupBranchId = (int)$validated['pickup_branch_id'];

        // 驗證取車據點屬於當前租戶且已啟用
        $validBranch = \App\Models\Branch::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('id', $pickupBranchId)
            ->exists();

        if (!$validBranch) {
            return ApiResponse::error('取車據點無效，請確認據點屬於您的租戶且已啟用', null, [], 400);
        }

        // 驗證最短租期
        if (!\App\Models\Reservation::validateMinimumRentalTime($startAt, $endAt)) {
            return ApiResponse::error('最少租用時間為4小時', null, [], 422);
        }

        // 獲取所有狀態為AVAILABLE的車輛
        $availableVehicles = \App\Models\Vehicle::where('tenant_id', $tenant->id)
            ->where('status', 'AVAILABLE')
            ->with('pricing')
            ->get();

        // 過濾掉在指定時間區間內有衝突的車輛，或是處於維修時段內的車輛，或是無法在取車時間到位於指定據點的車輛
        $filteredVehicles = $availableVehicles->filter(function ($vehicle) use ($startAt, $endAt, $pickupBranchId) {
            // 檢查車輛是否處於維修時段內，如果維修時間與預約時間重疊，則不可租用
            if ($vehicle->maintenance_start_at && $vehicle->maintenance_expected_end_at) {
                $maintenanceStart = \Carbon\Carbon::parse($vehicle->maintenance_start_at);
                $maintenanceEnd = \Carbon\Carbon::parse($vehicle->maintenance_expected_end_at);

                // 檢查維修時間是否與預約時間有重疊
                if ($startAt->lessThan($maintenanceEnd) && $endAt->greaterThan($maintenanceStart)) {
                    return false;
                }
            }

            // 檢查車輛是否能在取車時間到位於指定的取車據點
            if (!$vehicle->canBeAtBranchAt($pickupBranchId, $startAt)) {
                return false;
            }

            // 檢查是否有時間衝突的預約
            return !\App\Models\Reservation::hasTimeConflict($vehicle->id, $startAt, $endAt);
        });

        // 手動分頁
        $perPage = 15;
        $currentPage = $request->input('page', 1);
        $items = $filteredVehicles->slice(($currentPage - 1) * $perPage, $perPage);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filteredVehicles->count(),
            $perPage,
            $currentPage
        );

        return ApiResponse::paginate(
            \App\Http\Resources\VehicleResource::collection($paginator),
            '取得可用車輛列表成功'
        );
    }

    /**
     * 計算指定車輛在指定租期內的租金
     */
    #[OA\Post(
        path: "/api/v1/public/vehicles/{id}/calculate-price",
        summary: "計算車輛租金",
        description: "試算指定車輛在指定租期內的租金總額，包含逐日計價明細",
        tags: ["Vehicles"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "X-Tenant-Slug", in: "header", required: true, schema: new OA\Schema(type: "string"), description: "租戶唯一識別碼")
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["start_at", "end_at"],
                properties: [
                    new OA\Property(property: "start_at", type: "string", format: "date-time", example: "2026-10-15 10:00:00"),
                    new OA\Property(property: "end_at", type: "string", format: "date-time", example: "2026-10-18 18:00:00")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "成功計算租金", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 403, description: "無法解析租戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 422, description: "參數驗證失敗或租期不合法", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function calculatePrice($tenantSlug, $id, \Illuminate\Http\Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
        }

        // 驗證請求參數
        $validated = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        // 查找車輛
        $vehicle = \App\Models\Vehicle::where('tenant_id', $tenant->id)
            ->with('pricing')
            ->where('id', $id)
            ->first();

        if (!$vehicle) {
            return ApiResponse::error('車輛不存在', null, [], 404);
        }

        $startAt = \Carbon\Carbon::parse($validated['start_at']);
        $endAt = \Carbon\Carbon::parse($validated['end_at']);

        // 驗證最短租期
        if (!\App\Models\Reservation::validateMinimumRentalTime($startAt, $endAt)) {
            return ApiResponse::error('最少租用時間為4小時', null, [], 422);
        }

        // 使用現有的定價服務計算租金
        try {
            $pricingService = app(\App\Services\PricingService::class);
            $rentalDays = $pricingService->calculateRentalDays($startAt, $endAt);

            // 生成逐日計價明細
            $holidays = \App\Models\Holiday::where('tenant_id', $tenant->id)
                ->get()
                ->pluck('date')
                ->map(fn($date) => \Carbon\Carbon::parse($date))
                ->toArray();

            $breakdown = [];
            $current = $startAt->copy();
            $pricing = $vehicle->pricing;

            for ($i = 0; $i < $rentalDays; $i++) {
                $isHoliday = collect($holidays)->contains(fn($holiday) => $holiday->isSameDay($current));
                $isWeekend = in_array($current->dayOfWeek, [0, 6]);

                if ($isHoliday) {
                    $price = $pricing->holiday_price;
                    $type = 'holiday';
                } elseif ($isWeekend) {
                    $price = $pricing->weekend_price;
                    $type = 'weekend';
                } else {
                    $price = $pricing->weekday_price;
                    $type = 'weekday';
                }

                $breakdown[] = [
                    'date' => $current->toDateString(),
                    'type' => $type,
                    'price' => (float)$price,
                ];

                $current->addDay();
            }

            $pricingData = \App\Models\Reservation::calculateAmount($vehicle, $startAt, $endAt);

            return ApiResponse::success([
                'vehicle' => new \App\Http\Resources\VehicleResource($vehicle),
                'period' => [
                    'start_at' => $startAt->toDateTimeString(),
                    'end_at' => $endAt->toDateTimeString(),
                    'duration_hours' => $startAt->diffInHours($endAt),
                ],
                'rental_days' => $rentalDays,
                'breakdown' => $breakdown,
                'total_amount' => (float)$pricingData['amount'],
            ], '租金試算成功');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, [], 400);
        }
    }
}
