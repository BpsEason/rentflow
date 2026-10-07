<?php

namespace Database\Factories;

use App\Models\VehiclePricing;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehiclePricingFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->inRandomOrder()->first() ?? Vehicle::factory()->create(['tenant_id' => $tenant->id]);

        $weekdayPrice = fake()->randomFloat(2, 1000, 3000);

        return [
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'weekday_price' => $weekdayPrice,
            'weekend_price' => $weekdayPrice * 1.2,
            'holiday_price' => $weekdayPrice * 1.5,
        ];
    }
}
