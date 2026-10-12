<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\TenantContext;
use App\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->clear();
        $tenant = $this->tenantContext->resolvePublicTenant();

        if (!$tenant) {
            return ApiResponse::error('無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務', null, [], 403);
        }

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
