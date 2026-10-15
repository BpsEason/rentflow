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
use Carbon\Carbon;

class CancelReservationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Customer $customer;
    private Vehicle $vehicle;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建測試租戶
        $this->tenant = Tenant::factory()->create([
            'is_public' => true,
            'slug' => 'test-tenant',
        ]);

        // 創建會員用戶
        $this->user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 手動將使用者關聯到租戶，符合正式環境的授權規則
        $this->user->tenants()->attach($this->tenant->id);

        // 創建客戶資料
        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => '測試會員',
            'phone' => '0912345678',
            'email' => $this->user->email,
            'id_number' => 'A123456789',
            'license_number' => '12345678',
            'license_expiry' => '2030-01-01',
            'address' => '台北市中山區',
            'is_active' => true,
        ]);

        // 創建可用的車輛
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 創建車輛定價
        VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
            'vehicle_id' => $this->vehicle->id,
            'weekday_price' => 1000,
            'weekend_price' => 1500,
            'holiday_price' => 2000,
        ]);

        // 創建一個PENDING狀態的預約供測試使用
        $this->reservation = Reservation::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => Carbon::now()->addDay()->setHour(10)->setMinute(0),
            'end_at' => Carbon::now()->addDays(3)->setHour(18)->setMinute(0),
            'status' => Reservation::STATUS_PENDING,
            'amount' => 3000,
            'rental_days' => 3,
        ]);
    }

    public function test_owner_can_cancel_pending_reservation()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        // 輸出響應內容以調試
        echo $response->getContent() . PHP_EOL;

        $response->assertStatus(200)
            ->assertJsonPath('message', '預約取消成功');

        // 驗證資料庫狀態已更新
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'status' => Reservation::STATUS_CANCELLED,
        ]);
    }

    public function test_owner_can_cancel_confirmed_reservation()
    {
        // 將預約狀態改為CONFIRMED
        $this->reservation->update(['status' => Reservation::STATUS_CONFIRMED]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'status' => Reservation::STATUS_CANCELLED,
        ]);
    }

    public function test_guest_cannot_cancel_reservation()
    {
        // 未登入狀態嘗試取消預約
        $response = $this->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        $response->assertStatus(401); // 未授權
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'status' => Reservation::STATUS_PENDING, // 狀態未改變
        ]);
    }

    public function test_other_member_cannot_cancel_others_reservation()
    {
        // 創建另一個用戶
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
        ]);

        // 創建另一個用戶的客戶資料
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherUser->id,
            'name' => '其他用戶',
            'phone' => '0987654321',
            'email' => $otherUser->email,
            'id_number' => 'A987654321',
            'license_number' => '87654321',
            'license_expiry' => '2031-01-01',
            'address' => '新北市板橋區',
            'is_active' => true,
        ]);

        // 以另一個用戶身份嘗試取消預約
        $response = $this->actingAs($otherUser, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        $response->assertStatus(404); // 找不到預約（因權限檢查）
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    public function test_cannot_cancel_reservation_from_another_tenant()
    {
        // 創建另一個租戶
        $anotherTenant = Tenant::factory()->create([
            'is_public' => true,
            'slug' => 'another-tenant',
        ]);

        // 創建另一個租戶的車輛和客戶
        $anotherCustomer = Customer::create([
            'tenant_id' => $anotherTenant->id,
            'user_id' => $this->user->id,
            'name' => '跨租戶用戶',
            'phone' => '0911111111',
            'email' => $this->user->email,
            'id_number' => 'A111111111',
            'license_number' => '11111111',
            'license_expiry' => '2032-01-01',
            'address' => '台中市',
            'is_active' => true,
        ]);

        $anotherVehicle = Vehicle::factory()->create([
            'tenant_id' => $anotherTenant->id,
            'status' => 'AVAILABLE',
        ]);

        VehiclePricing::factory()->create([
            'tenant_id' => $anotherTenant->id,
            'vehicle_id' => $anotherVehicle->id,
        ]);

        // 在另一個租戶創建預約
        $anotherReservation = Reservation::create([
            'tenant_id' => $anotherTenant->id,
            'customer_id' => $anotherCustomer->id,
            'vehicle_id' => $anotherVehicle->id,
            'start_at' => Carbon::now()->addDay()->setHour(10),
            'end_at' => Carbon::now()->addDays(3)->setHour(18),
            'status' => Reservation::STATUS_PENDING,
            'amount' => 3000,
            'rental_days' => 3,
        ]);

        // 嘗試用原租戶的路徑取消另一個租戶的預約
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$anotherReservation->id}/cancel");

        $response->assertStatus(404);
        $this->assertDatabaseHas('reservations', [
            'id' => $anotherReservation->id,
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    public function test_cannot_cancel_picked_up_reservation()
    {
        // 將預約狀態改為PICKED_UP
        $this->reservation->update(['status' => Reservation::STATUS_PICKED_UP]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        $response->assertStatus(400)
            ->assertJsonPath('message', '只有待確認或已確認狀態的預約可以被取消');
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'status' => Reservation::STATUS_PICKED_UP, // 狀態未改變
        ]);
    }

    public function test_cannot_cancel_returned_reservation()
    {
        // 將預約狀態改為RETURNED
        $this->reservation->update(['status' => Reservation::STATUS_RETURNED]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        $response->assertStatus(400);
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'status' => Reservation::STATUS_RETURNED,
        ]);
    }

    public function test_cannot_double_cancel_reservation()
    {
        // 第一次取消成功
        $this->reservation->cancel();

        // 再次嘗試取消
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/{$this->tenant->slug}/customer/reservations/{$this->reservation->id}/cancel");

        $response->assertStatus(400)
            ->assertJsonPath('message', '只有待確認或已確認狀態的預約可以被取消');
    }
}
