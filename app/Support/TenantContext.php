<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    protected ?Tenant $currentTenant = null;

    /**
     * Set the current tenant
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /** Resolve and validate the tenant selected by the public route. */
    public function resolvePublicTenant(): ?Tenant
    {
        $tenantSlug = request()?->route('tenantSlug');
        if (!$tenantSlug) {
            return null;
        }

        // 不論是否登入，公開路由都只能存取is_public=true的租戶
        // 已登入的使用者也可以存取公開租戶，不需要是該租戶的成員
        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('is_public', true)
            ->first();

        if ($tenant) {
            $this->setCurrentTenant($tenant);
        }

        return $tenant;
    }

    /**
     * Get the current tenant
     */
    public function getCurrentTenant(): ?Tenant
    {
        // 如果已經通過中間件解析了租戶（從URL的tenantSlug），直接返回
        // 公開路由已經在resolvePublicTenant中驗證過is_public=true，不論是否登入都可存取
        // 讓控制器自己處理權限驗證，這樣控制器可以返回正確的404而不是中間件返回403
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // 處理header傳入X-Tenant-ID的場景
        if (auth('api')->check()) {
            $user = auth('api')->user();
            $requestedTenantId = request()?->header('X-Tenant-ID');

            if ($requestedTenantId) {
                $tenant = Tenant::find($requestedTenantId);
                if ($tenant && $user->canAccessTenant($tenant)) {
                    $this->currentTenant = $tenant;
                    return $this->currentTenant;
                }
                return null;
            }

            // 只有當請求中沒有指定任何租戶時，才返回用戶的第一個租戶
            $this->currentTenant = $user->tenants()->first();
            return $this->currentTenant;
        }

        // 只有在API guard未認證時，才檢查web guard（後台Filament Session認證）
        if (auth('web')->check()) {
            $user = auth('web')->user();

            // 保留管理端的Super Admin邏輯，只在web guard（後台）環境下觸發
            if ($user->hasRole('Super Admin')) {
                $this->currentTenant = Tenant::first();
            } else {
                $this->currentTenant = $user->tenants()->first();
            }
        }

        return $this->currentTenant;
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?int
    {
        $tenant = $this->getCurrentTenant();

        return $tenant?->id;
    }

    /**
     * Clear the current tenant
     */
    public function clear(): void
    {
        $this->currentTenant = null;
    }
}
