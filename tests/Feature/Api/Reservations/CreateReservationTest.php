<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\VehiclePricing;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Domain\Reservations\Events\ReservationCreated;

class CreateReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_create_reservation(): void
    {
        $response = $this->postJson('/api/v1/reservations', []);
        $response->assertStatus(401);
    }

    public function test_can_create_reservation(): void
    {
        // 建立測試數據
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
            'weekday_price' => 1000,
            'weekend_price' => 1500,
            'holiday_price' => 2000,
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        Event::fake();

        // 呼叫 API
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        // 斷言成功
        $response->assertStatus(201);
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
            'meta',
        ]);

        // 斷言事件被觸發
        Event::assertDispatched(ReservationCreated::class);

        // 斷言數據庫中有記錄
        $this->assertDatabaseHas('reservations', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_cannot_create_reservation_with_missing_required_fields(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        // 缺少所有必填字段
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id', 'vehicle_id', 'start_at', 'end_at']);
    }

    public function test_cannot_create_reservation_with_invalid_customer(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        // 使用不存在的客戶ID
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => 99999, // 不存在的ID
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id']);
    }

    public function test_cannot_create_reservation_with_invalid_vehicle(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        // 使用不存在的車輛ID
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => 99999, // 不存在的ID
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vehicle_id']);
    }

    public function test_cannot_create_reservation_with_invalid_date_format(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 使用無效的日期格式
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => 'invalid-date',
                'end_at' => 'also-invalid',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_at', 'end_at']);
    }

    public function test_cannot_create_reservation_with_end_date_before_start_date(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 結束時間早於開始時間
        $startAt = now()->addDays(2)->setHour(9)->setMinute(0);
        $endAt = now()->addDay()->setHour(9)->setMinute(0);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_cannot_create_reservation_with_customer_from_another_tenant(): void
    {
        // 建立兩個租戶
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()->create();
        $user->tenants()->attach($tenantA); // 用戶屬於租戶A

        // 租戶A的車輛
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenantA->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // 租戶B的客戶
        $customerFromB = Customer::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        // 嘗試使用另一個租戶的客戶
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customerFromB->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        // 應該失敗，因為客戶不屬於當前租戶
        $response->assertStatus(400);
    }

    public function test_cannot_create_reservation_with_vehicle_from_another_tenant(): void
    {
        // 建立兩個租戶
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()->create();
        $user->tenants()->attach($tenantA); // 用戶屬於租戶A

        // 租戶A的客戶
        $customer = Customer::factory()->create([
            'tenant_id' => $tenantA->id,
        ]);

        // 租戶B的車輛
        $vehicleFromB = Vehicle::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenantB->id,
            'vehicle_id' => $vehicleFromB->id,
        ]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        // 嘗試使用另一個租戶的車輛
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleFromB->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        // 應該失敗，因為車輛不屬於當前租戶
        $response->assertStatus(400);
    }

    public function test_cannot_spoof_tenant_id_in_request(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()->create();
        $user->tenants()->attach($tenantA);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenantA->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenantA->id]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        // 嘗試偽造tenant_id
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'tenant_id' => $tenantB->id, // 故意發送錯誤的租戶ID
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        // 創建的預約應該使用正確的租戶ID（來自租戶上下文，而不是請求中的值）
        $this->assertDatabaseHas('reservations', [
            'tenant_id' => $tenantA->id,
        ]);
        $this->assertDatabaseMissing('reservations', [
            'tenant_id' => $tenantB->id,
        ]);
    }

    public function test_can_create_reservation_with_non_overlapping_time_same_vehicle(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // 創建第一個預約：1月1日-1月3日
        Reservation::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => 'PENDING',
            'amount' => 2000,
            'rental_days' => 2,
        ]);

        // 嘗試創建不重疊的預約：1月4日-1月6日
        $startAt = now()->addDays(4)->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        // 應該成功，因為時間不重疊
        $response->assertStatus(201);
        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_cannot_create_reservation_with_unavailable_vehicle(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        // 建立一個狀態為MAINTENANCE的車輛
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'MAINTENANCE', // 非AVAILABLE狀態
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => '車輛目前無法租用',
        ]);
    }

    public function test_cannot_create_reservation_with_time_conflict(): void
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

        // 創建一個已存在的預約
        \App\Models\Reservation::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => 'PENDING',
            'amount' => 2000,
            'rental_days' => 2,
        ]);

        // 嘗試創建時間衝突的預約
        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => '指定時間區間內車輛已被預約',
        ]);
    }

    public function test_cannot_create_reservation_with_less_than_minimum_hours(): void
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

        // 嘗試創建只有3小時的預約（不足最低4小時）
        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addHours(3);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => '最少租用時間為4小時',
        ]);
    }

    public function test_pricing_amount_is_snapshotted_and_not_affected_by_future_changes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $originalPricing = VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'weekday_price' => 1000,
            'weekend_price' => 1500,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $startAt = now()->addDay()->setHour(9)->setMinute(0);
        $endAt = $startAt->copy()->addDays(2);

        // 創建第一個預約
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt->toISOString(),
                'end_at' => $endAt->toISOString(),
            ]);

        $firstReservationId = $response->json('data.id');
        $firstAmount = $response->json('data.amount');

        // 更新定價
        $originalPricing->update([
            'weekday_price' => 2000, // 價格上漲
            'weekend_price' => 3000,
        ]);

        // 創建另一個新的預約，應該使用新價格
        $startAt2 = now()->addDays(10)->setHour(9)->setMinute(0);
        $endAt2 = $startAt2->copy()->addDays(2);
        $response2 = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reservations', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt2->toISOString(),
                'end_at' => $endAt2->toISOString(),
            ]);

        $secondAmount = $response2->json('data.amount');

        // 第一個預約的金額應該保持不變（快照），第二個預約使用新價格
        $this->assertDatabaseHas('reservations', [
            'id' => $firstReservationId,
            'amount' => $firstAmount,
        ]);
        $this->assertGreaterThan($firstAmount, $secondAmount);
    }
}
