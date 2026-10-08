<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();

        $statuses = [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_MAINTENANCE,
            Vehicle::STATUS_INACTIVE,
        ];

        return [
            'tenant_id' => $tenant->id,
            'name' => fake()->words(2, true),
            'plate_number' => strtoupper(fake()->bothify('???-###')),
            'status' => fake()->randomElement($statuses),
            'seats' => fake()->randomElement([5, 7, 8]),
            'description' => fake()->sentence(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Vehicle $vehicle) {
            if (!\App\Models\VehiclePricing::where('tenant_id', $vehicle->tenant_id)
                ->where('vehicle_id', $vehicle->id)
                ->exists()) {
                \App\Models\VehiclePricing::create([
                    'tenant_id' => $vehicle->tenant_id,
                    'vehicle_id' => $vehicle->id,
                    'weekday_price' => 1000.00,
                    'weekend_price' => 1200.00,
                    'holiday_price' => 1500.00,
                ]);
            }
        });
    }

    public function available(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);
    }
}
