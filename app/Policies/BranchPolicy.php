<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_branch');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        return $user->can('view_branch') && $this->belongsToAccessibleTenant($user, $branch);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_branch');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        return $user->can('update_branch') && $this->belongsToAccessibleTenant($user, $branch);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // 檢查是否有關聯的車輛或預約，若有則不允許刪除
        if ($branch->vehicles()->exists() || $branch->pickupReservations()->exists() || $branch->returnReservations()->exists()) {
            return false;
        }

        return $user->can('delete_branch') && $this->belongsToAccessibleTenant($user, $branch);
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_branch');
    }

    /**
     * Verify that the branch belongs to a tenant the user can access
     */
    private function belongsToAccessibleTenant(User $user, Branch $branch): bool
    {
        return $user->hasRole('Super Admin') || $user->tenants()->whereKey($branch->tenant_id)->exists();
    }
}
