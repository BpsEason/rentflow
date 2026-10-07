<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        $customer = Customer::where('tenant_id', $tenant->id)->inRandomOrder()->first() ?? Customer::factory()->create(['tenant_id' => $tenant->id]);
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->where('status', 'AVAILABLE')->inRandomOrder()->first() ?? Vehicle::factory()->create(['tenant_id' => $tenant->id]);

        $startAt = Carbon::now()->addDays(fake()->randomNumber(2, true) % 30);
        $endAt = (clone $startAt)->addHours(fake()->randomElement([4, 8, 24, 48, 72]));

        $statuses = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PICKED_UP,
            Reservation::STATUS_RETURNED,
            Reservation::STATUS_CANCELLED,
        ];

        $rentalDays = Reservation::calculateRentalDays($startAt, $endAt);
        $pricingData = Reservation::calculateAmount($vehicle, $startAt, $endAt);

        return [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => fake()->randomElement($statuses),
            'amount' => $pricingData['amount'],
            'rental_days' => $pricingData['rental_days'],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
    }

    public function pickedUp(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Reservation::STATUS_PICKED_UP,
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Reservation::STATUS_RETURNED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Reservation::STATUS_CANCELLED,
        ]);
    }
}
