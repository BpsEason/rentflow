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
            HolidaySeeder::class,
            CustomerSeeder::class,
            RentalDemoSeeder::class, // 先建立分店，再建立車輛
            VehicleSeeder::class,
            VehiclePricingSeeder::class,
            ReservationSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
