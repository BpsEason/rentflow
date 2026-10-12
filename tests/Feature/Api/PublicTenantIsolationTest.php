<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Tenant;
use App\Models\VehiclePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 不同租戶只能看到自己的車輛及價格
     */
    public function test_different_tenants_can_only_see_their_own_vehicles()
    {
        $tenant1 = Tenant::factory()->create(['is_public' => true, 'slug' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['is_public' => true, 'slug' => 'tenant2']);

        $vehicle1 = Vehicle::factory()->create([
            'tenant_id' => $tenant1->id,
            'status' => 'AVAILABLE',
        ]);
        $vehicle2 = Vehicle::factory()->create([
            'tenant_id' => $tenant2->id,
            'status' => 'AVAILABLE',
        ]);

        VehiclePricing::factory()->create([
            'tenant_id' => $tenant1->id,
            'vehicle_id' => $vehicle1->id,
            'weekday_price' => 1000,
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenant2->id,
            'vehicle_id' => $vehicle2->id,
            'weekday_price' => 1500,
        ]);

        // 測試tenant1只能看到自己的車輛
        $response1 = $this->getJson("/api/v1/public/{$tenant1->slug}/vehicles", [
            'X-Tenant-Slug' => $tenant1->slug
        ]);
        $response1->assertStatus(200);
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonPath('data.0.id', $vehicle1->id);
        $response1->assertJsonPath('data.0.pricing.weekday_price', 1000);

        // 測試tenant2只能看到自己的車輛
        $response2 = $this->getJson("/api/v1/public/{$tenant2->slug}/vehicles", [
            'X-Tenant-Slug' => $tenant2->slug
        ]);
        $response2->assertStatus(200);
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonPath('data.0.id', $vehicle2->id);
        $response2->assertJsonPath('data.0.pricing.weekday_price', 1500);
    }

    /**
     * 不存在的租戶返回 403
     */
    public function test_nonexistent_tenant_returns_403()
    {
        $response = $this->getJson('/api/v1/public/nonexistent/vehicles', [
            'X-Tenant-Slug' => 'nonexistent'
        ]);
        $response->assertStatus(403);
        $response->assertJsonPath('message', '無法解析租戶資訊，請確認租戶 slug 正確且租戶已開啟公開服務');
    }

    /**
     * 非公開的租戶無法被訪問
     */
    public function test_private_tenant_cannot_be_accessed()
    {
        $privateTenant = Tenant::factory()->create([
            'is_public' => false,
            'slug' => 'private-tenant'
        ]);

        $response = $this->getJson("/api/v1/public/{$privateTenant->slug}/vehicles", [
            'X-Tenant-Slug' => $privateTenant->slug
        ]);
        $response->assertStatus(403);
    }

    /**
     * 不能透過修改車輛 ID 跨租戶查詢
     */
    public function test_cannot_access_vehicle_from_another_tenant()
    {
        $tenant1 = Tenant::factory()->create(['is_public' => true, 'slug' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['is_public' => true, 'slug' => 'tenant2']);

        $vehicle2 = Vehicle::factory()->create([
            'tenant_id' => $tenant2->id,
            'status' => 'AVAILABLE',
        ]);

        $response = $this->getJson("/api/v1/public/{$tenant1->slug}/vehicles/{$vehicle2->id}", [
            'X-Tenant-Slug' => $tenant1->slug
        ]);
        $response->assertStatus(404);
        $response->assertJsonPath('message', '車輛不存在');
    }

    public function test_public_vehicle_detail_returns_the_selected_tenants_vehicle_and_pricing()
    {
        $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'detail-tenant']);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'weekday_price' => 1234,
        ]);

        $this->getJson("/api/v1/public/{$tenant->slug}/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $vehicle->id)
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.pricing.tenant_id', $tenant->id)
            ->assertJsonPath('data.pricing.weekday_price', 1234);
    }

    /**
     * 訪客預約會寫入正確 Tenant
     */
    public function test_guest_reservation_writes_to_correct_tenant()
    {
        $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'test-tenant']);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'AVAILABLE',
        ]);

        VehiclePricing::factory()->create([
            'tenant_id' => $tenant->id,
            'vehicle_id' => $vehicle->id,
            'weekday_price' => 1000,
            'weekend_price' => 1500,
            'holiday_price' => 2000,
        ]);

        $bookingData = [
            'vehicle_id' => $vehicle->id,
            'pickup_date' => date('Y-m-d', strtotime('+1 day')),
            'pickup_time' => '10:00',
            'return_date' => date('Y-m-d', strtotime('+3 days')),
            'return_time' => '18:00',
            'customer_name' => '測試訪客',
            'customer_phone' => '0912345678',
            'customer_email' => 'guest@example.com',
            'customer_id_number' => 'A123456789',
            'license_number' => '1234567890',
            'license_expiry' => date('Y-m-d', strtotime('+5 years')),
        ];

        $response = $this->postJson("/api/v1/public/{$tenant->slug}/reservations", $bookingData, [
            'X-Tenant-Slug' => $tenant->slug
        ]);
        $response->assertStatus(201);

        // 驗證Customer已創建在正確的租戶下
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'email' => 'guest@example.com',
        ]);

        // 驗證Reservation已創建在正確的租戶下
        $customer = Customer::where('email', 'guest@example.com')->first();
        $this->assertDatabaseHas('reservations', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * 不會將其他租戶的 Customer 直接關聯到新預約
     */
    public function test_cannot_associate_customer_from_another_tenant()
    {
        $tenant1 = Tenant::factory()->create(['is_public' => true, 'slug' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['is_public' => true, 'slug' => 'tenant2']);

        // 在tenant1中創建一個已有客戶
        $existingCustomer = Customer::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => 'customer@example.com',
        ]);

        // 在tenant2中創建可用車輛
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant2->id,
            'status' => 'AVAILABLE',
        ]);

        VehiclePricing::factory()->create([
            'tenant_id' => $tenant2->id,
            'vehicle_id' => $vehicle->id,
            'weekday_price' => 1000,
            'weekend_price' => 1500,
            'holiday_price' => 2000,
        ]);

        $bookingData = [
            'vehicle_id' => $vehicle->id,
            'pickup_date' => date('Y-m-d', strtotime('+1 day')),
            'pickup_time' => '10:00',
            'return_date' => date('Y-m-d', strtotime('+3 days')),
            'return_time' => '18:00',
            'customer_name' => '新訪客',
            'customer_phone' => '0987654321',
            'customer_email' => 'customer@example.com', // 使用相同的email，但屬於不同租戶
            'customer_id_number' => 'B987654321',
            'license_number' => '0987654321',
            'license_expiry' => date('Y-m-d', strtotime('+5 years')),
        ];

        $response = $this->postJson("/api/v1/public/{$tenant2->slug}/reservations", $bookingData, [
            'X-Tenant-Slug' => $tenant2->slug
        ]);
        $response->assertStatus(201);

        // 驗證tenant2中創建了新的客戶記錄
        $tenant2Customers = Customer::where('tenant_id', $tenant2->id)
            ->where('email', 'customer@example.com')
            ->count();
        $this->assertEquals(1, $tenant2Customers);

        // 系統中總共有兩個客戶記錄（不同租戶）
        $totalCustomers = Customer::where('email', 'customer@example.com')->count();
        $this->assertEquals(2, $totalCustomers);
    }
}
