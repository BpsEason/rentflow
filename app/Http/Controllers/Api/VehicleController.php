<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\Api\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Api\Vehicles\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

#[OA\Tag(name: "Vehicles", description: "車輛管理相關API")]
class VehicleController extends Controller
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
}
