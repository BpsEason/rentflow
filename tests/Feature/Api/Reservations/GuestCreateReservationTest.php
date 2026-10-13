<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCreateReservationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建測試租戶（與系統的單租戶邏輯一致，第一個租戶）
        $this->tenant = Tenant::factory()->create([
            'is_public' => true,
        ]);
    }

    public function test_guest_can_create_reservation_with_valid_data()
    {
        // 創建可用的車輛
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 創建車輛定價（必需，因為UseCases需要計算價格）
        \App\Models\VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
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

        // 訪客未登入也能提交預約，使用新的租戶專屬路徑
        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/reservations", $bookingData, [
            'X-Tenant-Slug' => $this->tenant->slug
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('message', '預約建立成功');

        // 驗證Customer已自動創建
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'name' => '測試訪客',
            'email' => 'guest@example.com',
            'phone' => '0912345678',
            'id_number' => 'A123456789',
            'license_number' => '1234567890',
        ]);

        // 驗證Reservation已創建並與客戶關聯
        $customer = Customer::where('email', 'guest@example.com')->first();
        $this->assertDatabaseHas('reservations', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_guest_cannot_create_reservation_with_invalid_dates()
    {
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 創建車輛定價
        \App\Models\VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
            'vehicle_id' => $vehicle->id,
            'weekday_price' => 1000,
            'weekend_price' => 1500,
            'holiday_price' => 2000,
        ]);

        $bookingData = [
            'vehicle_id' => $vehicle->id,
            'pickup_date' => date('Y-m-d', strtotime('-1 day')), // 過去的日期
            'pickup_time' => '10:00',
            'return_date' => date('Y-m-d', strtotime('+1 day')),
            'return_time' => '18:00',
            'customer_name' => '測試訪客',
            'customer_phone' => '0912345678',
            'customer_email' => 'guest@example.com',
            'customer_id_number' => 'A123456789',
            'license_number' => '1234567890',
            'license_expiry' => date('Y-m-d', strtotime('+5 years')),
        ];

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/reservations", $bookingData, [
            'X-Tenant-Slug' => $this->tenant->slug
        ]);
        $response->assertStatus(422); // 驗證錯誤
    }

    public function test_guest_cannot_create_reservation_when_vehicle_is_unavailable()
    {
        // 創建已被租用的車輛
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'RENTED', // 不可用狀態
        ]);

        // 創建車輛定價
        \App\Models\VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
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

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/reservations", $bookingData, [
            'X-Tenant-Slug' => $this->tenant->slug
        ]);
        $response->assertStatus(400); // 業務錯誤
    }

    public function test_existing_customer_is_matched_when_email_duplicate()
    {
        // 先創建一個已存在的客戶
        $existingCustomer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@example.com',
            'name' => '原有客戶',
        ]);

        // 創建可用的車輛
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 創建車輛定價
        \App\Models\VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
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
            'customer_name' => '更新名稱',
            'customer_phone' => '0987654321',
            'customer_email' => 'existing@example.com', // 使用相同的email
            'customer_id_number' => 'B987654321',
            'license_number' => '0987654321',
            'license_expiry' => date('Y-m-d', strtotime('+5 years')),
        ];

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/reservations", $bookingData, [
            'X-Tenant-Slug' => $this->tenant->slug
        ]);
        $response->assertStatus(201);

        // 驗證客戶資料已更新，但仍是同一個客戶
        $this->assertDatabaseHas('customers', [
            'id' => $existingCustomer->id,
            'name' => '更新名稱',
            'phone' => '0987654321',
            'email' => 'existing@example.com',
        ]);

        // 系統只會有一個客戶記錄
        $this->assertEquals(1, Customer::where('email', 'existing@example.com')->count());
    }

    public function test_guest_cannot_access_protected_reservation_endpoints()
    {
        // 訪客嘗試訪問需要認證的會員預約列表端點
        $tenant = Tenant::factory()->create(['slug' => 'test-tenant', 'is_public' => true]);
        $response = $this->getJson("/api/v1/{$tenant->slug}/customer/reservations");
        $response->assertStatus(401); // 未授權
    }
}
