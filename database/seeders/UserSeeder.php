<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@rentflow.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        // Get all tenants
        $tenants = Tenant::all();

        // Tenant users data
        $tenantUsers = [
            'taipei-rental' => [
                [
                    'name' => '台北租車 Admin',
                    'email' => 'taipei-admin@rentflow.test',
                    'role' => 'admin',
                    'system_role' => 'Tenant Admin',
                ],
                [
                    'name' => '台北租車 Staff 01',
                    'email' => 'taipei-staff01@rentflow.test',
                    'role' => 'staff',
                    'system_role' => 'Tenant Staff',
                ],
                [
                    'name' => '台北租車 Staff 02',
                    'email' => 'taipei-staff02@rentflow.test',
                    'role' => 'staff',
                    'system_role' => 'Tenant Staff',
                ],
            ],
            'taichung-rental' => [
                [
                    'name' => '台中租車 Admin',
                    'email' => 'taichung-admin@rentflow.test',
                    'role' => 'admin',
                    'system_role' => 'Tenant Admin',
                ],
                [
                    'name' => '台中租車 Staff 01',
                    'email' => 'taichung-staff01@rentflow.test',
                    'role' => 'staff',
                    'system_role' => 'Tenant Staff',
                ],
                [
                    'name' => '台中租車 Staff 02',
                    'email' => 'taichung-staff02@rentflow.test',
                    'role' => 'staff',
                    'system_role' => 'Tenant Staff',
                ],
            ],
            'kaohsiung-rental' => [
                [
                    'name' => '高雄租車 Admin',
                    'email' => 'kaohsiung-admin@rentflow.test',
                    'role' => 'admin',
                    'system_role' => 'Tenant Admin',
                ],
                [
                    'name' => '高雄租車 Staff 01',
                    'email' => 'kaohsiung-staff01@rentflow.test',
                    'role' => 'staff',
                    'system_role' => 'Tenant Staff',
                ],
                [
                    'name' => '高雄租車 Staff 02',
                    'email' => 'kaohsiung-staff02@rentflow.test',
                    'role' => 'staff',
                    'system_role' => 'Tenant Staff',
                ],
            ],
        ];

        foreach ($tenants as $tenant) {
            if (isset($tenantUsers[$tenant->slug])) {
                foreach ($tenantUsers[$tenant->slug] as $userData) {
                    // Create or update user
                    $user = User::updateOrCreate(
                        ['email' => $userData['email']],
                        [
                            'name' => $userData['name'],
                            'password' => Hash::make('password'),
                        ]
                    );

                    // Assign system role
                    $user->assignRole($userData['system_role']);

                    // Attach user to tenant with pivot role if not already attached
                    if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
                        $user->tenants()->attach($tenant->id, ['role' => $userData['role']]);
                    }
                }
            }
        }
    }
}
