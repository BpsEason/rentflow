<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => '台北租車',
                'slug' => 'taipei-rental',
            ],
            [
                'name' => '台中租車',
                'slug' => 'taichung-rental',
            ],
            [
                'name' => '高雄租車',
                'slug' => 'kaohsiung-rental',
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::updateOrCreate(
                ['slug' => $tenant['slug']],
                ['name' => $tenant['name']]
            );
        }
    }
}
