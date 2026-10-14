<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
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

#[OA\Tag(name: "Reservations", description: "預約管理相關API")]
class ReservationsController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
        // 中間件：除了訪客預約端點外，其他都需要認證
        $this->middleware('auth:api')->except(['guestStore']);
        $this->middleware(function ($request, $next) {
            // 公開端點不需要檢查登入用戶的租戶上下文
            if ($request->route()->getActionMethod() === 'guestStore') {
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
        path: "/api/v1/reservations",
        summary: "取得所有預約",
        description: "取得目前租戶的所有預約列表",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "成功取得預約列表", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function index(ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservations = $reservationRepository->getAllForTenant($tenantId);

        return ApiResponse::success(
            ReservationResource::collection($reservations),
            '取得預約列表成功'
        );
    }

    #[OA\Get(
        path: "/api/v1/reservations/{reservation}",
        summary: "取得單一預約詳情",
        description: "取得指定預約的詳細資訊",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "reservation", in: "path", required: true, description: "預約ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得預約詳情", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "預約不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
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

    #[OA\Post(
        path: "/api/v1/reservations",
        summary: "創建新預約",
        description: "建立一個新的車輛預約",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["vehicle_id", "customer_id", "start_at", "end_at", "pickup_branch_id", "return_branch_id"],
                properties: [
                    new OA\Property(property: "vehicle_id", type: "integer", example: 1, description: "要預約的車輛ID"),
                    new OA\Property(property: "customer_id", type: "integer", example: 1, description: "客戶ID"),
                    new OA\Property(property: "start_at", type: "string", format: "date-time", example: "2024-01-01T10:00:00Z", description: "取車時間"),
                    new OA\Property(property: "end_at", type: "string", format: "date-time", example: "2024-01-05T10:00:00Z", description: "還車時間"),
                    new OA\Property(property: "pickup_branch_id", type: "integer", example: 1, description: "取車據點ID，必須屬於當前租戶且已啟用"),
                    new OA\Property(property: "return_branch_id", type: "integer", example: 1, description: "還車據點ID，必須屬於當前租戶且已啟用，支援跨店還車"),
                    new OA\Property(property: "notes", type: "string", example: "需要兒童座椅")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "預約創建成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 409, description: "車輛時間衝突", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 422, description: "驗證錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
    public function store(CreateReservationRequest $request, CreateReservation $useCase): JsonResponse
    {
        try {
            $validated = $request->validated();

            // 使用新的DTO結構
            $data = CreateReservationData::fromArray($validated);

            $reservation = $useCase->execute($data);
            $reservation->load(['customer', 'vehicle', 'order']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '預約建立成功',
                [],
                201
            );
        } catch (\Throwable $e) {
            \Log::error('Guest reservation error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->handleException($e);
        }
    }

    #[OA\Post(
        path: "/api/v1/reservations/{reservation}/confirm",
        summary: "確認預約",
        description: "將待確認的預約標記為已確認",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "reservation", in: "path", required: true, description: "預約ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "預約確認成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 409, description: "預約狀態無法確認", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function confirm($reservationId, ConfirmReservation $useCase): JsonResponse
    {
        try {
            $reservation = $useCase->execute($reservationId);
            $reservation->load(['customer', 'vehicle', 'order']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '預約確認成功'
            );
        } catch (\Throwable $e) {
            \Log::error('Guest reservation error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->handleException($e);
        }
    }

    #[OA\Post(
        path: "/api/v1/reservations/{reservation}/cancel",
        summary: "取消預約",
        description: "取消現有預約",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "reservation", in: "path", required: true, description: "預約ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "reason", type: "string", example: "客戶取消行程")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "預約取消成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 409, description: "預約狀態無法取消", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function cancel($reservationId, CancelReservation $useCase, Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $reservation = $useCase->execute($reservationId, $reason);
            $reservation->load(['customer', 'vehicle', 'order']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '預約取消成功'
            );
        } catch (\Throwable $e) {
            \Log::error('Guest reservation error: ' . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            return $this->handleException($e);
        }
    }

    #[OA\Post(
        path: "/api/v1/reservations/{reservation}/pickup",
        summary: "辦理取車",
        description: "將已確認的預約標記為已取車",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "reservation", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "取車成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "預約不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 409, description: "預約狀態無效", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function pickup($reservationId, ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservation = $reservationRepository->findForTenant($reservationId, $tenantId);

        if (!$reservation) {
            return ApiResponse::error('預約不存在', null, [], 404);
        }

        try {
            $reservation->pickUp();
            $reservation->load(['customer', 'vehicle', 'order']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '辦理取車成功'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[OA\Post(
        path: "/api/v1/reservations/{reservation}/return",
        summary: "辦理歸還",
        description: "將已取車的預約標記為已歸還",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "reservation", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "歸還成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 404, description: "預約不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 409, description: "預約狀態無效", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function return($reservationId, ReservationRepositoryInterface $reservationRepository): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $reservation = $reservationRepository->findForTenant($reservationId, $tenantId);

        if (!$reservation) {
            return ApiResponse::error('預約不存在', null, [], 404);
        }

        try {
            $reservation->return();
            $reservation->load(['customer', 'vehicle', 'pickupBranch', 'returnBranch']);

            return ApiResponse::success(
                new ReservationResource($reservation),
                '辦理歸還成功'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[OA\Post(
        path: "/api/v1/public/reservations",
        summary: "訪客創建預約",
        description: "未登入的訪客可以創建車輛預約，系統會自動創建或匹配客戶資料",
        tags: ["Reservations"],
        parameters: [
            new OA\Parameter(name: "X-Tenant-Slug", in: "header", required: true, schema: new OA\Schema(type: "string"), description: "租戶唯一識別碼")
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["vehicle_id", "pickup_date", "pickup_time", "return_date", "return_time", "customer_name", "customer_phone", "customer_email", "customer_id_number", "license_number", "license_expiry"],
                properties: [
                    new OA\Property(property: "vehicle_id", type: "integer", example: 1),
                    new OA\Property(property: "pickup_date", type: "string", format: "date", example: "2026-10-15"),
                    new OA\Property(property: "pickup_time", type: "string", example: "10:00"),
                    new OA\Property(property: "return_date", type: "string", format: "date", example: "2026-10-18"),
                    new OA\Property(property: "return_time", type: "string", example: "18:00"),
                    new OA\Property(property: "customer_name", type: "string", example: "陳志明"),
                    new OA\Property(property: "customer_phone", type: "string", example: "0912345678"),
                    new OA\Property(property: "customer_email", type: "string", example: "chen@example.com"),
                    new OA\Property(property: "customer_id_number", type: "string", example: "A123456789"),
                    new OA\Property(property: "license_number", type: "string", example: "1234567890"),
                    new OA\Property(property: "license_expiry", type: "string", format: "date", example: "2031-10-10"),
                    new OA\Property(property: "address", type: "string", example: "台北市大安區...", nullable: true),
                    new OA\Property(property: "emergency_contact_name", type: "string", example: "王小美", nullable: true),
                    new OA\Property(property: "emergency_contact_phone", type: "string", example: "0987654321", nullable: true),
                    new OA\Property(property: "notes", type: "string", example: "需要兒童座椅", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "預約建立成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 403, description: "無法解析租戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 400, description: "參數錯誤或車輛不可用", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function guestStore(\App\Http\Requests\Reservations\StoreGuestReservationRequest $request, CreateReservation $useCase): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();

            if (!$tenant) {
                return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
            }

            // 租戶已由TenantContext驗證，tenant_id將由後端自動設定，不信任前端任何提交的tenant_id

            // Reject foreign-tenant vehicle IDs before creating or updating customer data.
            if (!\App\Models\Vehicle::where('tenant_id', $tenant->id)
                ->whereKey($request->vehicle_id)
                ->exists()) {
                return ApiResponse::error('車輛不存在', null, [], 404);
            }

            // 驗證取車和還車據點都屬於當前租戶且已啟用
            if (!\App\Models\Branch::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereKey($request->pickup_branch_id)
                ->exists()) {
                return ApiResponse::error('取車據點不存在或已停用', null, [], 404);
            }

            if (!\App\Models\Branch::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereKey($request->return_branch_id)
                ->exists()) {
                return ApiResponse::error('還車據點不存在或已停用', null, [], 404);
            }

            // 合併日期和時間
            $startAt = $request->pickup_date . ' ' . $request->pickup_time;
            $endAt = $request->return_date . ' ' . $request->return_time;

            // 查找或創建客戶
            $customer = \App\Models\Customer::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email' => $request->customer_email,
                ],
                [
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'id_number' => $request->customer_id_number,
                    'license_number' => $request->license_number,
                    'license_expiry' => $request->license_expiry,
                    'address' => $request->address,
                    'emergency_contact_name' => $request->emergency_contact_name,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                    'is_active' => true,
                ]
            );

            // 如果客戶已存在，更新最新資訊
            if (!$customer->wasRecentlyCreated) {
                $customer->update([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'id_number' => $request->customer_id_number,
                    'license_number' => $request->license_number,
                    'license_expiry' => $request->license_expiry,
                    'address' => $request->address,
                    'emergency_contact_name' => $request->emergency_contact_name,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                ]);
            }

            // 使用現有的CreateReservationUseCases創建預約
            $createData = \App\Domain\Reservations\DTO\CreateReservationData::fromArray([
                'vehicle_id' => $request->vehicle_id,
                'customer_id' => $customer->id,
                'pickup_branch_id' => $request->pickup_branch_id,
                'return_branch_id' => $request->return_branch_id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $request->notes,
            ]);

            $reservation = $useCase->execute($createData);
            $reservation->load(['customer', 'vehicle', 'pickupBranch', 'returnBranch']);

            return ApiResponse::success(
                new \App\Http\Resources\ReservationResource($reservation),
                '預約建立成功',
                [],
                201
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
