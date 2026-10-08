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
