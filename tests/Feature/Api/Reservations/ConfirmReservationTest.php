<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\VehiclePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_confirm_pending_reservation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $vehiclePricing = VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 創建一個待確認的預約
        $reservation = Reservation::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => Reservation::STATUS_PENDING,
            'amount' => 2000,
            'rental_days' => 2,
        ]);

        // 呼叫確認 API
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/reservations/{$reservation->id}/confirm");

        $response->assertStatus(200);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
    }

    public function test_cannot_confirm_already_confirmed_reservation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $vehiclePricing = VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 創建一個已確認的預約
        $reservation = Reservation::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => Reservation::STATUS_CONFIRMED,
            'amount' => 2000,
            'rental_days' => 2,
        ]);

        // 再次嘗試確認
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/reservations/{$reservation->id}/confirm");

        $response->assertStatus(409);
    }

    public function test_cannot_confirm_cancelled_reservation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $vehiclePricing = VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 創建一個已取消的預約
        $reservation = Reservation::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => Reservation::STATUS_CANCELLED,
            'amount' => 2000,
            'rental_days' => 2,
        ]);

        // 嘗試確認已取消的預約
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/reservations/{$reservation->id}/confirm");

        $response->assertStatus(409);
    }

    public function test_cannot_confirm_reservation_from_other_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenantB->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenantB->id]);
        $reservation = Reservation::create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => Reservation::STATUS_PENDING,
            'amount' => 2000,
            'rental_days' => 2,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/reservations/{$reservation->id}/confirm");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_confirm_reservation(): void
    {
        $response = $this->postJson('/api/v1/reservations/1/confirm');

        $response->assertStatus(401);
    }
}
