<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_reservation');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        $userTenants = $user->tenants->pluck('id')->toArray();
        return $user->can('view_reservation') && in_array($reservation->tenant_id, $userTenants);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_reservation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        $userTenants = $user->tenants->pluck('id')->toArray();
        return $user->can('update_reservation') && in_array($reservation->tenant_id, $userTenants);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        $userTenants = $user->tenants->pluck('id')->toArray();
        return $user->can('delete_reservation') && in_array($reservation->tenant_id, $userTenants);
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_reservation');
    }
}
