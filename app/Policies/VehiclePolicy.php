<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Vehicle;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiclePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Vehicle');
    }

    public function view(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->can('View:Vehicle') && $this->belongsToAccessibleTenant($authUser, $vehicle);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Vehicle');
    }

    public function update(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->can('Update:Vehicle') && $this->belongsToAccessibleTenant($authUser, $vehicle);
    }

    public function delete(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->can('Delete:Vehicle') && $this->belongsToAccessibleTenant($authUser, $vehicle);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Vehicle');
    }

    public function restore(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->can('Restore:Vehicle') && $this->belongsToAccessibleTenant($authUser, $vehicle);
    }

    public function forceDelete(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->can('ForceDelete:Vehicle') && $this->belongsToAccessibleTenant($authUser, $vehicle);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Vehicle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Vehicle');
    }

    public function replicate(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->can('Replicate:Vehicle') && $this->belongsToAccessibleTenant($authUser, $vehicle);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Vehicle');
    }

    /**
     * Verify that the vehicle belongs to a tenant the user can access
     */
    private function belongsToAccessibleTenant(AuthUser $authUser, Vehicle $vehicle): bool
    {
        return $authUser->hasRole('Super Admin') || $authUser->tenants()->whereKey($vehicle->tenant_id)->exists();
    }
}
