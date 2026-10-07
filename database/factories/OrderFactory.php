<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $reservation = Reservation::whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PICKED_UP])
            ->inRandomOrder()
            ->firstOrCreate(
                ['tenant_id' => Tenant::factory()],
                Reservation::factory()->raw()
            );

        static $orderCounter = [];
        $tenantId = $reservation->tenant_id;
        if (!isset($orderCounter[$tenantId])) {
            $orderCounter[$tenantId] = 1;
        }

        $orderNumber = 'ORD-' . str_pad($tenantId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($orderCounter[$tenantId], 6, '0', STR_PAD_LEFT);
        $orderCounter[$tenantId]++;

        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        return [
            'tenant_id' => $tenantId,
            'reservation_id' => $reservation->id,
            'order_number' => $orderNumber,
            'status' => $this->faker->randomElement($statuses),
            'total_amount' => $reservation->amount,
        ];
    }
}
