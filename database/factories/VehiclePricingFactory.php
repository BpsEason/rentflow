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
        $weekdayPrice = fake()->randomFloat(2, 1000, 3000);

        return [
            'tenant_id' => Tenant::factory(),
            'vehicle_id' => Vehicle::factory(),
            'weekday_price' => $weekdayPrice,
            'weekend_price' => round($weekdayPrice * 1.2, 2),
            'holiday_price' => round($weekdayPrice * 1.5, 2),
        ];
    }

    public function create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
    {
        $rawAttributes = is_array($attributes) ? $attributes : [];

        if (isset($rawAttributes['vehicle_id'], $rawAttributes['tenant_id'])) {
            $existing = VehiclePricing::where('tenant_id', $rawAttributes['tenant_id'])
                ->where('vehicle_id', $rawAttributes['vehicle_id'])
                ->first();

            if ($existing) {
                $existing->update(array_merge($this->definition(), $rawAttributes));
                return $existing;
            }
        }

        return parent::create($attributes, $parent);
    }
}
