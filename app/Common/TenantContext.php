<?php

namespace App\Common;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * 從目前認證的使用者解析租戶（API 適用）
     * 假設使用者只屬於一個租戶，或取第一個租戶
     */
    public function resolveFromAuth(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $tenant = $user->tenants->first();

        if ($tenant) {
            $this->setTenant($tenant);
        }
    }
}
