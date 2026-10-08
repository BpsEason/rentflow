<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\VehiclePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_access_reservation_from_another_tenant(): void
    {
        // 建立兩個獨立的租戶
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // 使用者只屬於租戶A
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        // 在租戶B建立一個預約
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => 'AVAILABLE',
        ]);

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

        // 嘗試查看另一個租戶的預約
        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/reservations/{$reservation->id}");

        // 應該返回404，因為跨租戶看不到這個預約
        $response->assertStatus(404);
        $response->assertJson([
            'message' => '預約不存在',
        ]);
    }

    public function test_cannot_modify_reservation_from_another_tenant(): void
    {
        // 建立兩個獨立的租戶
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // 使用者只屬於租戶A
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        // 在租戶B建立一個預約
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => 'AVAILABLE',
        ]);

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

        // 嘗試確認另一個租戶的預約
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/reservations/{$reservation->id}/confirm");

        // 應該返回404，因為跨租戶看不到這個預約
        $response->assertStatus(404);
        $response->assertJson([
            'message' => '預約不存在',
        ]);

        // 數據庫狀態不應該改變
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    public function test_cannot_cancel_reservation_from_another_tenant(): void
    {
        // 建立兩個獨立的租戶
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // 使用者只屬於租戶A
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        // 在租戶B建立一個預約
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => 'AVAILABLE',
        ]);

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

        // 嘗試取消另一個租戶的預約
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/reservations/{$reservation->id}/cancel");

        $response->assertStatus(404);
    }

    public function test_only_sees_own_tenant_reservations_in_list(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        // 在租戶A建立3個預約
        $vehicleA = Vehicle::factory()->create(['tenant_id' => $tenantA->id]);
        $customerA = Customer::factory()->create(['tenant_id' => $tenantA->id]);
        Reservation::factory()->count(3)->create([
            'tenant_id' => $tenantA->id,
            'vehicle_id' => $vehicleA->id,
            'customer_id' => $customerA->id,
        ]);

        // 在租戶B建立5個預約
        $vehicleB = Vehicle::factory()->create(['tenant_id' => $tenantB->id]);
        $customerB = Customer::factory()->create(['tenant_id' => $tenantB->id]);
        Reservation::factory()->count(5)->create([
            'tenant_id' => $tenantB->id,
            'vehicle_id' => $vehicleB->id,
            'customer_id' => $customerB->id,
        ]);

        // 使用者只能看到自己租戶的3個預約
        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reservations');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_without_any_tenant_cannot_create_reservation(): void
    {
        // 建立一個不屬於任何租戶的使用者
        $user = User::factory()->create();

        // 嘗試創建預約
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => 1,
                'vehicle_id' => 1,
                'start_at' => now()->addDay()->toISOString(),
                'end_at' => now()->addDays(3)->toISOString(),
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => '無法解析租戶資訊，使用者未關聯任何租戶'
        ]);
    }
}
