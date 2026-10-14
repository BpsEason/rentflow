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
        // 如果已經設置了currentTenant（比如公開API的中間件設置的），直接返回
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // 優先檢查API guard（前台JWT認證） - 確保前台API永遠使用正確的JWT認證使用者
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
