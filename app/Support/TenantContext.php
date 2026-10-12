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
        if (!$this->currentTenant && Auth::check()) {
            $user = Auth::user();
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

            // 保留管理端的Super Admin邏輯，但只在管理端認證環境下觸發
            if (!$this->currentTenant && $user->hasRole('Super Admin')) {
                $this->currentTenant = Tenant::first();
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
