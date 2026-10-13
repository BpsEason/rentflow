<?php

namespace App\Services;

use App\Domain\Rental\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerMemberPasswordService
{
    public function set(User $manager, Customer $customer, string $managerPassword, string $newPassword): void
    {
        Gate::forUser($manager)->authorize('update', $customer);

        if (! Hash::check($managerPassword, $manager->password)) {
            throw ValidationException::withMessages([
                'manager_password' => '管理者目前密碼不正確。',
            ]);
        }

        $customer->user()->firstOrFail()->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();
    }
}
