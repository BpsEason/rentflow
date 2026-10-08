<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customers\StoreCustomerRequest;
use App\Http\Requests\Api\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(name="Customers", description="客戶管理相關API")
 */
class CustomerController extends Controller
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
     *     path="/api/v1/customers",
     *     summary="取得客戶列表",
     *     description="取得目前租戶的所有客戶列表",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得客戶列表",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $customers = Customer::where('tenant_id', $tenantId)->paginate(15);

        return ApiResponse::paginate(
            CustomerResource::collection($customers),
            '取得客戶列表成功'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers",
     *     summary="建立客戶",
     *     description="建立新客戶",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone"},
     *             @OA\Property(property="name", type="string", example="王小明"),
     *             @OA\Property(property="email", type="string", format="email", example="ming@example.com"),
     *             @OA\Property(property="phone", type="string", example="0912345678"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="客戶建立成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     )
     * )
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $validated = $request->validated();
        $validated['tenant_id'] = $tenantId;

        $customer = Customer::create($validated);

        return ApiResponse::success(
            new CustomerResource($customer),
            '建立客戶成功',
            [],
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{customer}",
     *     summary="取得單一客戶詳情",
     *     description="取得指定客戶詳情",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得客戶詳情",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(response=404, description="客戶不存在")
     * )
     */
    public function show($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $customer = Customer::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$customer) {
            return ApiResponse::error('客戶不存在', null, [], 404);
        }

        return ApiResponse::success(
            new CustomerResource($customer),
            '取得客戶詳情成功'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/customers/{customer}",
     *     summary="更新客戶資訊",
     *     description="更新指定客戶資訊",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="更新客戶成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(response=404, description="客戶不存在")
     * )
     */
    public function update(UpdateCustomerRequest $request, $id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $customer = Customer::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$customer) {
            return ApiResponse::error('客戶不存在', null, [], 404);
        }

        $customer->update($request->validated());

        return ApiResponse::success(
            new CustomerResource($customer),
            '更新客戶成功'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/customers/{customer}",
     *     summary="刪除客戶",
     *     description="刪除指定客戶",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="刪除客戶成功"),
     *     @OA\Response(response=404, description="客戶不存在")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $customer = Customer::where('tenant_id', $tenantId)->where('id', $id)->first();

        if (!$customer) {
            return ApiResponse::error('客戶不存在', null, [], 404);
        }

        $customer->delete();

        return ApiResponse::success(null, '刪除客戶成功');
    }
}
