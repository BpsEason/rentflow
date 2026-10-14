<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

#[OA\Tag(name: "Branches", description: "據點管理相關API")]
class BranchController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * 公開取得目前租戶的所有可用據點列表（不需要登入）
     */
    #[OA\Get(
        path: "/api/v1/public/{tenantSlug}/branches",
        summary: "公開取得據點列表",
        description: "取得目前租戶的所有已啟用據點列表，供前台公開存取",
        tags: ["Branches"],
        parameters: [
            new OA\Parameter(name: "tenantSlug", in: "path", required: true, schema: new OA\Schema(type: "string"), description: "租戶唯一識別碼")
        ],
        responses: [
            new OA\Response(response: 200, description: "成功取得據點列表", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 403, description: "無法解析租戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function index($tenantSlug): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
        }

        // 只返回已啟用的據點
        $branches = Branch::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'address', 'phone', 'business_hours']);

        return ApiResponse::success(
            BranchResource::collection($branches),
            '取得據點列表成功'
        );
    }
}
