<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Domain\Rental\Models\VehiclePricing;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiclePricingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehiclePricing');
    }

    public function view(AuthUser $authUser, VehiclePricing $vehiclePricing): bool
    {
        return $authUser->can('View:VehiclePricing');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehiclePricing');
    }

    public function update(AuthUser $authUser, VehiclePricing $vehiclePricing): bool
    {
        return $authUser->can('Update:VehiclePricing');
    }

    public function delete(AuthUser $authUser, VehiclePricing $vehiclePricing): bool
    {
        return $authUser->can('Delete:VehiclePricing');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:VehiclePricing');
    }

    public function restore(AuthUser $authUser, VehiclePricing $vehiclePricing): bool
    {
        return $authUser->can('Restore:VehiclePricing');
    }

    public function forceDelete(AuthUser $authUser, VehiclePricing $vehiclePricing): bool
    {
        return $authUser->can('ForceDelete:VehiclePricing');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehiclePricing');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehiclePricing');
    }

    public function replicate(AuthUser $authUser, VehiclePricing $vehiclePricing): bool
    {
        return $authUser->can('Replicate:VehiclePricing');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehiclePricing');
    }
}
