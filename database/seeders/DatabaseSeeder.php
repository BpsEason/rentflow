<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TenantSeeder::class,
            UserSeeder::class,
            VehicleSeeder::class,
            VehiclePricingSeeder::class,
            HolidaySeeder::class,
            CustomerSeeder::class,
            ReservationSeeder::class,
        ]);
    }
}
