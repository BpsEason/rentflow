<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create platform-level roles
        Role::updateOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        Role::updateOrCreate(
            ['name' => 'Tenant Admin', 'guard_name' => 'web'],
            ['name' => 'Tenant Admin', 'guard_name' => 'web']
        );

        Role::updateOrCreate(
            ['name' => 'Tenant Staff', 'guard_name' => 'web'],
            ['name' => 'Tenant Staff', 'guard_name' => 'web']
        );
    }
}
