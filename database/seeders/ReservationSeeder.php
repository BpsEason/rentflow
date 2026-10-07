<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $customers = Customer::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            $vehicles = Vehicle::where('tenant_id', $tenant->id)
                ->where('status', 'AVAILABLE')
                ->get();

            if ($customers->isEmpty() || $vehicles->isEmpty()) {
                continue;
            }

            $this->seedReservationsForTenant($tenant, $customers, $vehicles);
        }
    }

    private function seedReservationsForTenant($tenant, $customers, $vehicles): void
    {
        $now = Carbon::now()->startOfDay();

        // 為每台車建立一組「不衝突」且狀態完整的預約
        foreach ($vehicles as $vehicleIndex => $vehicle) {
            $customerPool = $customers->shuffle();

            $scenarios = [
                // 1. 已完成（過去）
                [
                    'start'  => $now->copy()->subDays(12)->setTime(10, 0),
                    'end'    => $now->copy()->subDays(10)->setTime(18, 0),
                    'status' => \App\Models\Reservation::STATUS_RETURNED,
                ],
                // 2. 進行中（已取車）
                [
                    'start'  => $now->copy()->subDays(1)->setTime(9, 0),
                    'end'    => $now->copy()->addDays(2)->setTime(18, 0),
                    'status' => \App\Models\Reservation::STATUS_PICKED_UP,
                ],
                // 3. 已確認（即將到來）
                [
                    'start'  => $now->copy()->addDays(4)->setTime(10, 0),
                    'end'    => $now->copy()->addDays(6)->setTime(18, 0),
                    'status' => \App\Models\Reservation::STATUS_CONFIRMED,
                ],
                // 4. 待確認
                [
                    'start'  => $now->copy()->addDays(9)->setTime(9, 0),
                    'end'    => $now->copy()->addDays(11)->setTime(17, 0),
                    'status' => \App\Models\Reservation::STATUS_PENDING,
                ],
                // 5. 已取消（不佔用庫存）
                [
                    'start'  => $now->copy()->addDays(15)->setTime(10, 0),
                    'end'    => $now->copy()->addDays(16)->setTime(18, 0),
                    'status' => \App\Models\Reservation::STATUS_CANCELLED,
                ],
            ];

            foreach ($scenarios as $i => $scenario) {
                $customer = $customerPool[$i % $customerPool->count()];

                // 使用Reservation::createReservation建立正確的預約
                Reservation::createReservation([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'start_at' => $scenario['start'],
                    'end_at' => $scenario['end'],
                    'status' => $scenario['status'],
                ]);
            }
        }
    }
}
