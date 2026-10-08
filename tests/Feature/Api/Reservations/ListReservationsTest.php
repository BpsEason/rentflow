<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_tenant_reservations(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 建立多個預約
        Reservation::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reservations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer_id',
                    'vehicle_id',
                    'start_at',
                    'end_at',
                    'status',
                    'amount',
                ]
            ]
        ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_reservations(): void
    {
        $response = $this->getJson('/api/v1/reservations');

        $response->assertStatus(401);
    }

    public function test_user_cannot_see_other_tenants_reservations(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenantB->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenantB->id]);
        Reservation::factory()->create([
            'tenant_id' => $tenantB->id,
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reservations');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
}
