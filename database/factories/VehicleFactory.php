<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\Tenant;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();

        // 取得或建立一個當前租戶的活躍據點作為車輛的當前據點
        $branch = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->first()
            ?? Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);

        $statuses = [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_MAINTENANCE,
            Vehicle::STATUS_INACTIVE,
        ];

        $fuelTypes = [
            Vehicle::FUEL_PETROL,
            Vehicle::FUEL_DIESEL,
            Vehicle::FUEL_ELECTRIC,
            Vehicle::FUEL_HYBRID,
        ];

        $transmissions = [
            Vehicle::TRANSMISSION_AUTOMATIC,
            Vehicle::TRANSMISSION_MANUAL,
        ];

        return [
            'tenant_id' => $tenant->id,
            'current_branch_id' => $branch->id,
            'name' => fake()->words(2, true),
            'plate_number' => strtoupper(fake()->bothify('???-###')),
            'status' => fake()->randomElement($statuses),
            'seats' => fake()->randomElement([5, 7, 8]),
            'description' => fake()->sentence(),
            'manufacture_year' => fake()->randomElement([2019, 2020, 2021, 2022, 2023, 2024]),
            'mileage' => fake()->randomNumber(5, false), // 0-99999 公里
            'fuel_type' => fake()->randomElement($fuelTypes),
            'transmission' => fake()->randomElement($transmissions),
            'maintenance_start_at' => null,
            'maintenance_expected_end_at' => null,
            'maintenance_notes' => null,
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
