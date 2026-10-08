<?php

namespace Tests\Feature\E2E;

use App\Models\Reservation;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class FullRentalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立兩個獨立的租戶
        $this->tenantA = Tenant::factory()->create(['name' => '租戶A']);
        $this->tenantB = Tenant::factory()->create(['name' => '租戶B']);

        // 建立屬於各租戶的使用者
        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();

        // 將使用者關聯到其租戶
        $this->userA->tenants()->attach($this->tenantA->id);
        $this->userB->tenants()->attach($this->tenantB->id);
    }

    /**
     * 完整的租車生命週期E2E測試
     */
    public function test_full_rental_lifecycle_works_correctly()
    {
        $this->actingAs($this->userA);

        // === 步驟1：在租戶A下建立必要的資料 ===
        $customerA = Customer::factory()->create(['tenant_id' => $this->tenantA->id]);
        $vehicleX = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicleX->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);

        // === 步驟2：建立第一個預約Reservation A ===
        // 使用明確的平日（2026-10-07 星期一到2026-10-09 星期三）
        $startAtA = Carbon::parse('2026-10-07 09:00:00');
        $endAtA = Carbon::parse('2026-10-09 09:00:00');

        $reservationA = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleX->id,
            'start_at' => $startAtA,
            'end_at' => $endAtA,
        ]);

        $this->assertEquals(Reservation::STATUS_PENDING, $reservationA->status);
        $this->assertEquals(2, $reservationA->rental_days); // 2天
        $this->assertEquals(2000, $reservationA->amount);  // 1000 * 2 = 2000（都是平日）

        // === 步驟3：確認預約 ===
        $reservationA->update(['status' => Reservation::STATUS_CONFIRMED]);
        $reservationA->refresh();
        $this->assertEquals(Reservation::STATUS_CONFIRMED, $reservationA->status);

        // === 步驟4：建立訂單Order A ===
        $orderA = Order::createFromReservation($reservationA);
        $this->assertEquals($reservationA->amount, $orderA->total_amount);
        $this->assertEquals($reservationA->id, $orderA->reservation_id);
        $this->assertEquals($this->tenantA->id, $orderA->tenant_id);
        $this->assertEquals(Order::STATUS_PENDING, $orderA->status);

        // 驗證無法對同一個預約建立第二個訂單
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order already exists for this reservation.');
        Order::createFromReservation($reservationA);

        // === 步驟5：確認訂單 ===
        $orderA->update(['status' => Order::STATUS_CONFIRMED]);
        $orderA->refresh();
        $this->assertEquals(Order::STATUS_CONFIRMED, $orderA->status);

        // === 步驟6：顧客取車 ===
        $reservationA->update(['status' => Reservation::STATUS_PICKED_UP]);
        $reservationA->refresh();
        $this->assertEquals(Reservation::STATUS_PICKED_UP, $reservationA->status);

        // === 步驟7：顧客還車 ===
        $reservationA->update(['status' => Reservation::STATUS_RETURNED]);
        $reservationA->refresh();
        $this->assertEquals(Reservation::STATUS_RETURNED, $reservationA->status);

        // === 步驟8：完成訂單 ===
        $orderA->update(['status' => Order::STATUS_COMPLETED]);
        $orderA->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $orderA->status);

        // === 驗證車輛庫存可以再次被預約 ===
        // 建立接續的預約C，時間從10/12 09:00開始，應可成功建立
        $startAtC = Carbon::parse('2026-10-12 09:00:00');
        $endAtC = Carbon::parse('2026-10-13 09:00:00');

        $reservationC = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleX->id,
            'start_at' => $startAtC,
            'end_at' => $endAtC,
        ]);
        $this->assertInstanceOf(Reservation::class, $reservationC);
    }

    /**
     * 測試車輛時間衝突保護
     */
    public function test_vehicle_time_conflict_protection()
    {
        $this->actingAs($this->userA);

        $customerA = Customer::factory()->create(['tenant_id' => $this->tenantA->id]);
        $vehicleX = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicleX->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);

        // 建立預約A：10/10 09:00 ~ 10/12 09:00
        $startAtA = Carbon::parse('2026-10-10 09:00:00');
        $endAtA = Carbon::parse('2026-10-12 09:00:00');
        Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleX->id,
            'start_at' => $startAtA,
            'end_at' => $endAtA,
            'status' => Reservation::STATUS_CONFIRMED, // 標記為已確認，會阻擋重疊預約
        ]);

        // 嘗試建立預約B：10/11 10:00 ~ 10/13 10:00 - 應該失敗（時間重疊）
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('指定時間區間內車輛已被預約');

        $startAtB = Carbon::parse('2026-10-11 10:00:00');
        $endAtB = Carbon::parse('2026-10-13 10:00:00');
        Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleX->id,
            'start_at' => $startAtB,
            'end_at' => $endAtB,
        ]);
    }

    /**
     * 測試[start_at, end_at)邊界條件 - 前一個結束時間等於下一個開始時間應該可以建立
     */
    public function test_boundary_condition_allows_consecutive_reservations()
    {
        $this->actingAs($this->userA);

        $customerA = Customer::factory()->create(['tenant_id' => $this->tenantA->id]);
        $vehicleX = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicleX->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);

        // 預約A：10/10 09:00 ~ 10/12 09:00
        $startAtA = Carbon::parse('2026-10-10 09:00:00');
        $endAtA = Carbon::parse('2026-10-12 09:00:00');
        $reservationA = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleX->id,
            'start_at' => $startAtA,
            'end_at' => $endAtA,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        // 預約C：10/12 09:00 ~ 10/13 09:00 - 應該成功（時間剛好接續）
        $startAtC = Carbon::parse('2026-10-12 09:00:00');
        $endAtC = Carbon::parse('2026-10-13 09:00:00');
        $reservationC = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleX->id,
            'start_at' => $startAtC,
            'end_at' => $endAtC,
        ]);

        $this->assertInstanceOf(Reservation::class, $reservationC);
        $this->assertNotEquals($reservationA->id, $reservationC->id);
    }

    /**
     * 測試Tenant隔離 - 租戶A無法存取租戶B的資料
     */
    public function test_tenant_isolation()
    {
        // 在租戶B建立資料
        $customerB = Customer::factory()->create(['tenant_id' => $this->tenantB->id]);
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicleB->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);

        // 以租戶A的使用者身分登入
        $this->actingAs($this->userA);

        // 嘗試使用租戶B的車輛建立預約 - 應該失敗，因為車輛不屬於目前租戶
        $this->expectException(\Exception::class);

        $startAt = Carbon::parse('2026-10-10 09:00:00');
        $endAt = Carbon::parse('2026-10-11 13:00:00');
        Reservation::createReservation([
            'tenant_id' => $this->tenantA->id, // 使用租戶A的ID
            'customer_id' => $customerB->id,   // 但使用租戶B的客戶
            'vehicle_id' => $vehicleB->id,    // 但使用租戶B的車輛
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
    }

    /**
     * 測試各種定價場景
     */
    public function test_pricing_scenarios()
    {
        $this->actingAs($this->userA);

        // 為每個測試使用不同的車輛避免時間衝突
        $customerA = Customer::factory()->create(['tenant_id' => $this->tenantA->id]);

        // 測試1：平日（週一到週五）
        $vehicle1 = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicle1->pricing()->update([
            'weekday_price' => 1000,  // 平日
            'weekend_price' => 1200,  // 週末
            'holiday_price' => 1500,  // 假日
        ]);
        $startAt = Carbon::parse('2026-10-06 09:00:00'); // 星期一
        $endAt = Carbon::parse('2026-10-07 09:00:00');   // 星期二
        $reservation1 = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicle1->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
        $this->assertEquals(1000, $reservation1->amount);

        // 測試2：週末（週六日）
        $vehicle2 = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicle2->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);
        $startAt = Carbon::parse('2026-10-11 09:00:00'); // 星期六
        $endAt = Carbon::parse('2026-10-12 09:00:00');   // 星期日
        $reservation2 = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicle2->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
        $this->assertEquals(1200, $reservation2->amount);

        // 測試3：不足24小時仍算一天
        $vehicle3 = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicle3->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);
        $startAt = Carbon::parse('2026-10-06 09:00:00');
        $endAt = Carbon::parse('2026-10-06 13:00:00'); // 只有4小時
        $reservation3 = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicle3->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
        $this->assertEquals(1, $reservation3->rental_days);
        $this->assertEquals(1000, $reservation3->amount);

        // 測試4：跨日期（包含平日和週末）
        $vehicle4 = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicle4->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);
        // 2026-10-09 星期五（平日）到2026-10-11 星期日
        $startAt = Carbon::parse('2026-10-09 09:00:00'); // 星期五（平日）
        $endAt = Carbon::parse('2026-10-11 09:00:00');   // 星期日（兩天，一天平日一天週末）
        $reservation4 = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicle4->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
        $this->assertEquals(2200, $reservation4->amount); // 1000 + 1200 = 2200
    }

    /**
     * 測試預約金額Snapshot - 定價變更後不會影響已建立的預約
     */
    public function test_reservation_amount_snapshot()
    {
        $this->actingAs($this->userA);

        $customerA = Customer::factory()->create(['tenant_id' => $this->tenantA->id]);
        $vehicleA = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicleA->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);

        // 建立預約
        $startAt = Carbon::parse('2026-10-06 09:00:00');
        $endAt = Carbon::parse('2026-10-07 09:00:00');
        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleA->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $originalAmount = $reservation->amount;
        $this->assertEquals(1000, $originalAmount);

        // 修改車輛定價
        $vehicleA->pricing()->update([
            'weekday_price' => 2000, // 價格翻倍
        ]);

        // 重新整理預約資料，確保金額不變
        $reservation->refresh();
        $this->assertEquals(1000, $reservation->amount);
        $this->assertEquals($originalAmount, $reservation->amount);
    }

    /**
     * 測試負向案例：無法從未確認的預約建立訂單
     */
    public function test_cannot_create_order_from_unconfirmed_reservation()
    {
        $this->actingAs($this->userA);

        $customerA = Customer::factory()->create(['tenant_id' => $this->tenantA->id]);
        $vehicleA = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'status' => 'AVAILABLE'
        ]);
        $vehicleA->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);

        // 建立PENDING狀態的預約
        $startAt = Carbon::parse('2026-10-10 09:00:00');
        $endAt = Carbon::parse('2026-10-12 09:00:00');
        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleA->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_PENDING, // 維持待確認狀態
        ]);

        // 嘗試建立訂單應該失敗
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create order from an unconfirmed reservation.');
        Order::createFromReservation($reservation);
    }
}
