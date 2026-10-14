<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        $customer = Customer::where('tenant_id', $tenant->id)->inRandomOrder()->first() ?? Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 建立或取得同一租戶下的兩個已啟用據點
        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        if ($branches->count() < 2) {
            // 建立兩個新的已啟用據點
            $branch1 = Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
            $branch2 = Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
            $pickupBranch = $branch1;
            $returnBranch = $branch2;
        } else {
            $pickupBranch = $branches[0];
            $returnBranch = $branches[1];
        }

        // 確保車輛的當前據點就是取車據點，這樣 canBeAtBranchAt 驗證才能通過
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->where('status', 'AVAILABLE')->where('current_branch_id', $pickupBranch->id)->inRandomOrder()->first()
            ?? Vehicle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'AVAILABLE', 'current_branch_id' => $pickupBranch->id]);

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
            'pickup_branch_id' => $pickupBranch->id,
            'return_branch_id' => $returnBranch->id,
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
