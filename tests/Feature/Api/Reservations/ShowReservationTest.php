<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_tenant_reservation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $reservation = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/reservations/{$reservation->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'customer_id',
                'vehicle_id',
                'start_at',
                'end_at',
                'status',
                'amount',
                'rental_days',
            ],
            'meta'
        ]);
        $response->assertJsonPath('data.id', $reservation->id);
        $response->assertJsonPath('data.status', 'PENDING');
    }

    public function test_user_cannot_view_reservation_from_other_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenantB->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenantB->id]);
        $reservation = Reservation::factory()->create([
            'tenant_id' => $tenantB->id,
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/reservations/{$reservation->id}");

        $response->assertStatus(404);
        $response->assertJson([
            'message' => '預約不存在',
        ]);
    }

    public function test_returns_404_for_non_existent_reservation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reservations/999');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_view_reservation(): void
    {
        $response = $this->getJson('/api/v1/reservations/1');

        $response->assertStatus(401);
    }
}
