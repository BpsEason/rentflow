<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('Tenant Admin')
            || $user->hasRole('Tenant Staff');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // All tenant users (Admin and Staff) can only view users in their shared tenants
        return $user->tenants()->whereHas('users', function ($query) use ($model) {
            $query->where('users.id', $model->id);
        })->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Only Tenant Admin can update users in their own tenants
        if ($user->hasRole('Tenant Admin')) {
            return $user->tenants()->whereHas('users', function ($query) use ($model) {
                $query->where('users.id', $model->id);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Only Tenant Admin can delete users in their own tenants
        if ($user->hasRole('Tenant Admin')) {
            return $user->tenants()->whereHas('users', function ($query) use ($model) {
                $query->where('users.id', $model->id);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('Super Admin');
    }
}
