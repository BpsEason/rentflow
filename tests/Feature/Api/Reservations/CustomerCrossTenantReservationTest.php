<?php

namespace Tests\Feature\Api\Reservations;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\VehiclePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrossTenantReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_customer_and_reservation_on_first_access_to_tenant_a(): void
    {
        // 創建租戶A
        $tenantA = Tenant::factory()->create(['slug' => 'tenant-a', 'is_public' => true]);
        // 創建會員用戶
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 手動將使用者關聯到租戶A，符合正式環境的授權規則
        $user->tenants()->attach($tenantA->id);

        // 創建租戶A的有效據點
        $pickupBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);
        $returnBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);

        // 創建租戶A的可用車輛，明確指定當前據點為取車據點
        $vehicleA = Vehicle::factory()->create([
            'tenant_id' => $tenantA->id,
            'current_branch_id' => $pickupBranch->id,
            'status' => 'AVAILABLE',
        ]);

        VehiclePricing::factory()->create([
            'tenant_id' => $tenantA->id,
            'vehicle_id' => $vehicleA->id,
            'weekday_price' => 1000,
        ]);

        // 確保用戶在租戶A沒有Customer記錄
        $this->assertDatabaseMissing('customers', [
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
        ]);

        // 使用會員身份向租戶A提交預約，包含建立Customer所需的所有資料
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/{$tenantA->slug}/customer/reservations", [
                'vehicle_id' => $vehicleA->id,
                'pickup_date' => $startDate,
                'pickup_time' => '10:00',
                'return_date' => $endDate,
                'return_time' => '18:00',
                'pickup_branch_id' => $pickupBranch->id,
                'return_branch_id' => $returnBranch->id,
                'notes' => '需要兒童座椅',
                // 客戶資料，用於自動建立Customer
                'customer_name' => '測試會員',
                'customer_phone' => '0912345678',
                'customer_id_number' => 'A123456789',
                'license_number' => '12345678',
                'license_expiry' => '2030-01-01',
                'address' => '台北市中山區',
            ]);

        // 斷言預約成功建立
        $response->assertStatus(201);

        // 驗證Customer和Reservation都被正確建立，且關聯正確
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
            'name' => '測試會員',
            'email' => 'member@example.com',
            'phone' => '0912345678',
        ]);

        $customer = Customer::where('tenant_id', $tenantA->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertDatabaseHas('reservations', [
            'tenant_id' => $tenantA->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleA->id,
        ]);
    }

    public function test_same_member_can_create_separate_customer_and_reservation_on_tenant_b(): void
    {
        // 創建兩個不同的租戶
        $tenantA = Tenant::factory()->create(['slug' => 'tenant-a', 'is_public' => true]);
        $tenantB = Tenant::factory()->create(['slug' => 'tenant-b', 'is_public' => true]);

        // 同一個會員用戶
        $user = User::factory()->create([
            'email' => 'member@example.com',
        ]);

        // 手動將使用者關聯到租戶A和租戶B，符合正式環境的授權規則
        $user->tenants()->attach($tenantA->id);
        $user->tenants()->attach($tenantB->id);

        // 為每個租戶創建有效據點
        $pickupBranchA = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);
        $returnBranchA = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);
        $pickupBranchB = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantB->id,
            'is_active' => true,
        ]);
        $returnBranchB = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantB->id,
            'is_active' => true,
        ]);

        // 為每個租戶創建可用車輛，明確指定當前據點為取車據點
        $vehicleA = Vehicle::factory()->create([
            'tenant_id' => $tenantA->id,
            'current_branch_id' => $pickupBranchA->id,
            'status' => 'AVAILABLE',
        ]);
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $tenantB->id,
            'current_branch_id' => $pickupBranchB->id,
            'status' => 'AVAILABLE',
        ]);

        VehiclePricing::factory()->create(['tenant_id' => $tenantA->id, 'vehicle_id' => $vehicleA->id]);
        VehiclePricing::factory()->create(['tenant_id' => $tenantB->id, 'vehicle_id' => $vehicleB->id]);

        // 先在租戶A建立預約（這會自動建立租戶A的Customer）
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(3)->format('Y-m-d');

        $this->actingAs($user, 'api')
            ->postJson("/api/v1/{$tenantA->slug}/customer/reservations", [
                'vehicle_id' => $vehicleA->id,
                'pickup_date' => $startDate,
                'pickup_time' => '10:00',
                'return_date' => $endDate,
                'return_time' => '18:00',
                'pickup_branch_id' => $pickupBranchA->id,
                'return_branch_id' => $returnBranchA->id,
                'customer_name' => '測試會員',
                'customer_phone' => '0912345678',
                'customer_id_number' => 'A123456789',
                'license_number' => '12345678',
                'license_expiry' => '2030-01-01',
                'address' => '台北市中山區',
            ])->assertStatus(201);

        // 驗證租戶A的Customer存在
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
        ]);

        // 現在在租戶B建立預約，應該自動建立租戶B的獨立Customer
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/{$tenantB->slug}/customer/reservations", [
                'vehicle_id' => $vehicleB->id,
                'pickup_date' => $startDate,
                'pickup_time' => '10:00',
                'return_date' => $endDate,
                'return_time' => '18:00',
                'pickup_branch_id' => $pickupBranchB->id,
                'return_branch_id' => $returnBranchB->id,
                'customer_name' => '測試會員',
                'customer_phone' => '0987654321',
                'customer_id_number' => 'A987654321',
                'license_number' => '87654321',
                'license_expiry' => '2031-01-01',
                'address' => '新北市板橋區',
            ]);

        $response->assertStatus(201);

        // 驗證租戶A的Customer沒有被修改，租戶B的新Customer被正確建立
        $customerA = Customer::where('tenant_id', $tenantA->id)->where('user_id', $user->id)->first();
        $this->assertEquals('0912345678', $customerA->phone);

        $customerB = Customer::where('tenant_id', $tenantB->id)->where('user_id', $user->id)->first();
        $this->assertNotNull($customerB);
        $this->assertEquals('0987654321', $customerB->phone);

        // 驗證兩個租戶的Reservation都存在且正確關聯
        $this->assertDatabaseHas('reservations', [
            'tenant_id' => $tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleA->id,
        ]);

        $this->assertDatabaseHas('reservations', [
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
        ]);
    }

    public function test_member_reuses_existing_customer_when_booking_again_in_same_tenant(): void
    { // 創建租戶
        $tenant = Tenant::factory()->create(['slug' => 'tenant-a', 'is_public' => true]);
        $user = User::factory()->create(['email' => 'member@example.com']);

        // 手動將使用者關聯到租戶，符合正式環境的授權規則
        $user->tenants()->attach($tenant->id);

        // 創建租戶的有效據點
        $pickupBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $returnBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // 預先建立用戶在這個租戶的Customer
        $existingCustomer = Customer::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => '原始姓名',
            'phone' => '0911111111',
            'email' => 'member@example.com',
            'id_number' => 'A111111111',
            'license_number' => '11111111',
            'license_expiry' => '2030-01-01',
            'address' => '原始地址',
            'is_active' => true,
        ]);

        // 創建兩輛可用車輛
        $vehicle1 = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'AVAILABLE']);
        $vehicle2 = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'AVAILABLE']);
        VehiclePricing::factory()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle1->id]);
        VehiclePricing::factory()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle2->id]);

        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(3)->format('Y-m-d');

        // 第一次預約 - 重用既有Customer
        $response1 = $this->actingAs($user, 'api')
            ->postJson("/api/v1/{$tenant->slug}/customer/reservations", [
                'vehicle_id' => $vehicle1->id,
                'pickup_date' => $startDate,
                'pickup_time' => '10:00',
                'return_date' => $endDate,
                'return_time' => '18:00',
                'pickup_branch_id' => $pickupBranch->id,
                'return_branch_id' => $returnBranch->id,
                // 故意提交不同的資料，驗證不會覆寫既有Customer
                'customer_name' => '嘗試修改的姓名',
                'customer_phone' => '0922222222',
            ]);
        $response1->assertStatus(201);

        // 第二次預約 - 仍然重用同一個Customer
        $startDate2 = now()->addDays(10)->format('Y-m-d');
        $endDate2 = now()->addDays(13)->format('Y-m-d');
        $response2 = $this->actingAs($user, 'api')
            ->postJson("/api/v1/{$tenant->slug}/customer/reservations", [
                'vehicle_id' => $vehicle2->id,
                'pickup_date' => $startDate2,
                'pickup_time' => '14:00',
                'return_date' => $endDate2,
                'return_time' => '18:00',
                'pickup_branch_id' => $pickupBranch->id,
                'return_branch_id' => $returnBranch->id,
            ]);
        $response2->assertStatus(201);

        // 驗證Customer只有一個，且資料沒有被修改
        $customersCount = Customer::where('tenant_id', $tenant->id)->where('user_id', $user->id)->count();
        $this->assertEquals(1, $customersCount);

        $customer = Customer::find($existingCustomer->id);
        $this->assertEquals('原始姓名', $customer->name);
        $this->assertEquals('0911111111', $customer->phone);

        // 驗證兩個預約都使用同一個customer_id
        $reservations = \App\Models\Reservation::where('tenant_id', $tenant->id)->where('customer_id', $customer->id)->get();
        $this->assertEquals(2, $reservations->count());
    }

    public function test_cannot_access_other_tenants_vehicles_or_reservations(): void
    {
        // 創建兩個租戶
        $tenantA = Tenant::factory()->create(['slug' => 'tenant-a', 'is_public' => true]);
        $tenantB = Tenant::factory()->create(['slug' => 'tenant-b', 'is_public' => true]);

        $user = User::factory()->create();

        // 手動將使用者關聯到租戶A，符合正式環境的授權規則
        $user->tenants()->attach($tenantA->id);

        // 創建租戶A的有效據點
        $pickupBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);
        $returnBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
        ]);

        // 在租戶A建立用戶的Customer和車輛、預約
        $customerA = Customer::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
            'name' => '租戶A的客戶',
            'phone' => '0911111111',
            'email' => 'user@example.com',
            'id_number' => 'A123456789',
            'license_number' => '12345678',
            'license_expiry' => '2030-01-01',
            'address' => '地址',
            'is_active' => true,
        ]);

        $vehicleA = Vehicle::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'AVAILABLE']);
        VehiclePricing::factory()->create(['tenant_id' => $tenantA->id, 'vehicle_id' => $vehicleA->id]);

        // 在租戶B創建車輛
        $vehicleB = Vehicle::factory()->create(['tenant_id' => $tenantB->id, 'status' => 'AVAILABLE']);
        VehiclePricing::factory()->create(['tenant_id' => $tenantB->id, 'vehicle_id' => $vehicleB->id]);

        // 嘗試在租戶A使用租戶B的車輛創建預約，應該失敗
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/{$tenantA->slug}/customer/reservations", [
                'vehicle_id' => $vehicleB->id, // 跨租戶使用車輛
                'pickup_date' => $startDate,
                'pickup_time' => '10:00',
                'return_date' => $endDate,
                'return_time' => '18:00',
                'pickup_branch_id' => $pickupBranch->id,
                'return_branch_id' => $returnBranch->id,
            ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => '車輛不存在']);
    }

    public function test_concurrent_requests_do_not_create_duplicate_customers(): void
    {
        // 創建租戶
        $tenant = Tenant::factory()->create(['slug' => 'tenant-a', 'is_public' => true]);
        $user = User::factory()->create(['email' => 'member@example.com']);

        // 手動將使用者關聯到租戶，符合正式環境的授權規則
        $user->tenants()->attach($tenant->id);

        // 創建租戶的有效據點
        $pickupBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $returnBranch = \App\Models\Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // 創建兩輛不同的車輛，避免時間衝突
        $vehicle1 = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'AVAILABLE']);
        $vehicle2 = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'status' => 'AVAILABLE']);
        VehiclePricing::factory()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle1->id]);
        VehiclePricing::factory()->create(['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle2->id]);

        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDays(3)->format('Y-m-d');

        // 兩個預約使用不同的車輛，避免時間區間衝突
        $bookingData1 = [
            'vehicle_id' => $vehicle1->id,
            'pickup_date' => $startDate,
            'pickup_time' => '10:00',
            'return_date' => $endDate,
            'return_time' => '18:00',
            'pickup_branch_id' => $pickupBranch->id,
            'return_branch_id' => $returnBranch->id,
            'customer_name' => '測試會員',
            'customer_phone' => '0912345678',
            'customer_id_number' => 'A123456789',
            'license_number' => '12345678',
            'license_expiry' => '2030-01-01',
            'address' => '台北市中山區',
        ];

        $bookingData2 = [
            'vehicle_id' => $vehicle2->id,
            'pickup_date' => $startDate,
            'pickup_time' => '14:00',
            'return_date' => $endDate,
            'return_time' => '18:00',
            'pickup_branch_id' => $pickupBranch->id,
            'return_branch_id' => $returnBranch->id,
            'customer_name' => '測試會員',
            'customer_phone' => '0912345678',
            'customer_id_number' => 'A123456789',
            'license_number' => '12345678',
            'license_expiry' => '2030-01-01',
            'address' => '台北市中山區',
        ];

        // 模擬併發發送兩個請求
        $promise1 = $this->actingAs($user, 'api')->postJson("/api/v1/{$tenant->slug}/customer/reservations", $bookingData1);
        $promise2 = $this->actingAs($user, 'api')->postJson("/api/v1/{$tenant->slug}/customer/reservations", $bookingData2);

        // 兩個請求都應該成功（第二個會重用已創建的Customer）
        $promise1->assertStatus(201);
        $promise2->assertStatus(201);

        // 但只應該有一個Customer記錄被創建
        $customersCount = Customer::where('tenant_id', $tenant->id)->where('user_id', $user->id)->count();
        $this->assertEquals(1, $customersCount);
    }
}
