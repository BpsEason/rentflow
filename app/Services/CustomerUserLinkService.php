<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class CustomerUserLinkService
{
    public function assertCanLink(?int $userId, int $tenantId, ?int $exceptCustomerId = null): void
    {
        if ($userId === null) {
            return;
        }

        $available = User::query()
            ->whereKey($userId)
            ->whereHas('tenants', fn ($query) => $query->whereKey($tenantId))
            ->whereDoesntHave('customers', function ($query) use ($tenantId, $exceptCustomerId): void {
                $query->where('tenant_id', $tenantId);
                if ($exceptCustomerId !== null) {
                    $query->whereKeyNot($exceptCustomerId);
                }
            })
            ->exists();

        if (! $available) {
            throw ValidationException::withMessages([
                'user_id' => '只能關聯同一租戶且尚未在此租戶建立客戶資料的會員帳號。',
            ]);
        }
    }
}
