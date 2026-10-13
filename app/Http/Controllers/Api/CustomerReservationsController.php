<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Customer;
use App\Models\Reservation;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * 前台客戶預約相關API控制器
 */
class CustomerReservationsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    #[OA\Get(
        path: "/api/v1/customer/reservations",
        summary: "取得當前客戶的預約列表",
        description: "獲取登入會員在當前租戶下的所有預約記錄",
        tags: ["Customer Reservations"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "成功取得預約列表", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "未找到客戶資料", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function index($tenantSlug, \App\Support\TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getCurrentTenant();
        if (!$tenant) {
            return ApiResponse::error('無效的租戶', null, [], 400);
        }

        $user = Auth::user();

        // 獲取用戶在當前租戶的Customer資料
        $customer = Customer::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return ApiResponse::success([], '您尚未在此租戶建立客戶資料');
        }

        // 獲取該客戶的所有預約，按建立時間倒序排序
        $reservations = Reservation::where('customer_id', $customer->id)
            ->with(['vehicle', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ApiResponse::success(
            ReservationResource::collection($reservations),
            '取得預約列表成功'
        );
    }

    #[OA\Get(
        path: "/api/v1/customer/reservations/{id}",
        summary: "取得單一預約詳情",
        description: "獲取登入會員在當前租戶下的特定預約的詳細資訊",
        tags: ["Customer Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "預約ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得預約詳情", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 403, description: "無權存取此預約", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "預約不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function show($tenantSlug, $id, \App\Support\TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getCurrentTenant();
        if (!$tenant) {
            return ApiResponse::error('無效的租戶', null, [], 400);
        }

        $user = Auth::user();

        // 獲取用戶在當前租戶的Customer資料
        $customer = Customer::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return ApiResponse::error('您尚未在此租戶建立客戶資料', null, [], 404);
        }

        // 查找預約，確保它屬於當前客戶和租戶
        $reservation = Reservation::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereHas('vehicle', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->with(['vehicle', 'order'])
            ->first();

        if (!$reservation) {
            return ApiResponse::error('預約不存在或您無權存取', null, [], 404);
        }

        return ApiResponse::success(
            new ReservationResource($reservation),
            '取得預約詳情成功'
        );
    }

    #[OA\Post(
        path: "/api/v1/customer/reservations",
        summary: "會員建立預約",
        description: "登入的會員使用已有的Customer資料建立車輛預約",
        tags: ["Customer Reservations"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["vehicle_id", "pickup_date", "pickup_time", "return_date", "return_time"],
                properties: [
                    new OA\Property(property: "vehicle_id", type: "integer", example: 1),
                    new OA\Property(property: "pickup_date", type: "string", format: "date", example: "2026-10-15"),
                    new OA\Property(property: "pickup_time", type: "string", example: "10:00"),
                    new OA\Property(property: "return_date", type: "string", format: "date", example: "2026-10-18"),
                    new OA\Property(property: "return_time", type: "string", example: "18:00"),
                    new OA\Property(property: "notes", type: "string", example: "需要兒童座椅", nullable: true),
                    // 客戶資料欄位 - 僅當用戶在當前租戶尚未建立Customer資料時需要
                    new OA\Property(property: "customer_name", type: "string", example: "張三", nullable: true),
                    new OA\Property(property: "customer_phone", type: "string", example: "0912345678", nullable: true),
                    new OA\Property(property: "customer_id_number", type: "string", example: "A123456789", nullable: true),
                    new OA\Property(property: "license_number", type: "string", example: "12345678", nullable: true),
                    new OA\Property(property: "license_expiry", type: "string", format: "date", example: "2030-01-01", nullable: true),
                    new OA\Property(property: "address", type: "string", example: "台北市中山區", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "預約建立成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "未找到客戶資料或車輛不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 400, description: "參數錯誤或車輛不可用", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function store($tenantSlug, \App\Http\Requests\Reservations\StoreCustomerReservationRequest $request, \App\Application\Reservations\UseCases\CreateReservation $useCase, \App\Support\TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getCurrentTenant();
        if (!$tenant) {
            return ApiResponse::error('無效的租戶', null, [], 400);
        }

        $user = Auth::user();

        // 使用數據庫事務確保Customer和Reservation要麼都成功，要麼都失敗
        return \DB::transaction(function () use ($tenant, $user, $request, $useCase) {
            // 先檢查車輛是否存在且屬於當前租戶，提前失敗可以避免不必要的Customer創建
            if (!\App\Models\Vehicle::where('tenant_id', $tenant->id)
                ->whereKey($request->vehicle_id)
                ->exists()) {
                return ApiResponse::error('車輛不存在', null, [], 404);
            }

            // 獲取用戶在當前租戶的Customer資料 - 使用鎖防止併發創建多個Customer
            $customer = Customer::where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            // 如果沒有Customer資料，但前端提交了完整的客戶資料，則自動建立
            if (!$customer) {
                // 檢查是否提交了建立Customer所需的所有欄位
                $requiredFields = [
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_id_number' => $request->customer_id_number,
                    'license_number' => $request->license_number,
                    'license_expiry' => $request->license_expiry,
                    'address' => $request->address,
                ];

                $missingFields = [];
                foreach ($requiredFields as $field => $value) {
                    if (empty($value)) {
                        $missingFields[] = $field;
                    }
                }

                if (!empty($missingFields)) {
                    return ApiResponse::error('您尚未在此租戶建立客戶資料，請填寫完整的個人資料', null, [], 404);
                }

                // 檢查同一租戶下phone是否已被使用（email來自用戶本身，不會重複）
                $existingCustomer = Customer::where('tenant_id', $tenant->id)
                    ->where('phone', $request->customer_phone)
                    ->lockForUpdate()
                    ->first();

                if ($existingCustomer) {
                    return ApiResponse::error('此電話已在此租戶註冊過', null, [], 400);
                }

                // 建立新的Customer資料
                try {
                    $customer = Customer::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => $user->id,
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'email' => $user->email,
                        'id_number' => $request->customer_id_number,
                        'license_number' => $request->license_number,
                        'license_expiry' => $request->license_expiry,
                        'address' => $request->address,
                        'emergency_contact_name' => $request->emergency_contact_name,
                        'emergency_contact_phone' => $request->emergency_contact_phone,
                        'is_active' => true,
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // 捕獲唯一性約束違反，說明併發情況下已經有其他請求創建了Customer
                    $customer = Customer::where('tenant_id', $tenant->id)
                        ->where('user_id', $user->id)
                        ->firstOrFail();
                }
            }

            // 合併日期和時間
            $startAt = $request->pickup_date . ' ' . $request->pickup_time;
            $endAt = $request->return_date . ' ' . $request->return_time;

            // 使用現有的CreateReservationUseCases創建預約
            $createData = \App\Domain\Reservations\DTO\CreateReservationData::fromArray([
                'vehicle_id' => $request->vehicle_id,
                'customer_id' => $customer->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $request->notes,
            ]);

            $reservation = $useCase->execute($createData);
            $reservation->load(['customer', 'vehicle']);

            return ApiResponse::success(
                new \App\Http\Resources\ReservationResource($reservation),
                '預約建立成功',
                [],
                201
            );
        });
    }

    #[OA\Post(
        path: "/api/v1/customer/reservations/{id}/cancel",
        summary: "會員取消預約",
        description: "登入的會員取消自己的預約，僅限PENDING或CONFIRMED狀態的預約",
        tags: ["Customer Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "預約ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "預約取消成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 403, description: "無權存取此預約或無法取消", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 404, description: "預約不存在", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function cancel($tenantSlug, $id, \App\Support\TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getCurrentTenant();
        if (!$tenant) {
            return ApiResponse::error('無效的租戶', null, [], 400);
        }

        $user = Auth::user();

        // 獲取用戶在當前租戶的Customer資料
        $customer = Customer::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return ApiResponse::error('您尚未在此租戶建立客戶資料', null, [], 404);
        }

        // 查找預約，確保它屬於當前客戶和租戶
        $reservation = Reservation::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereHas('vehicle', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->first();

        if (!$reservation) {
            return ApiResponse::error('預約不存在或您無權存取', null, [], 404);
        }

        // 使用數據庫事務處理狀態更新
        return \DB::transaction(function () use ($reservation) {
            try {
                $reservation->cancel();
                $reservation->load(['vehicle', 'order']);

                return ApiResponse::success(
                    new ReservationResource($reservation),
                    '預約取消成功'
                );
            } catch (\RuntimeException $e) {
                return ApiResponse::error($e->getMessage(), null, [], 400);
            }
        });
    }
}
