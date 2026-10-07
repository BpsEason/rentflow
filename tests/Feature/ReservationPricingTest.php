<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\VehiclePricing;
use App\Domain\Rental\Models\Customer;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPricingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Vehicle $vehicle;
    protected VehiclePricing $pricing;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試用租戶
        $this->tenant = Tenant::factory()->create();

        // 建立測試用車輛
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Vehicle',
            'status' => 'AVAILABLE',
        ]);

        // 更新現有的定價，而不是創建新的，因為 VehicleFactory 已經自動創建了
        $this->vehicle->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);
        $this->pricing = $this->vehicle->pricing;

        // 建立測試用客戶
        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function reservation_saves_price_snapshot_when_created()
    {
        $startAt = Carbon::parse('2026-10-09 10:00:00'); // 週四
        $endAt = Carbon::parse('2026-10-10 10:00:00');   // 1天

        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        // 驗證建立時的價格正確
        $this->assertEquals(1, $reservation->rental_days);
        $this->assertEquals(1000, $reservation->amount);

        // 修改車輛定價
        $this->pricing->update([
            'weekday_price' => 1500,
        ]);

        // 重新讀取預約，驗證價格快照不會改變
        $reservation->refresh();
        $this->assertEquals(1000, $reservation->amount);
    }

    /** @test */
    public function reservation_recalculates_price_when_dates_are_updated()
    {
        $startAt = Carbon::parse('2026-10-09 10:00:00'); // 週四
        $endAt = Carbon::parse('2026-10-10 10:00:00');   // 1天

        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->assertEquals(1000, $reservation->amount);

        // 更新預約日期，延長到週末
        $newEndAt = Carbon::parse('2026-10-12 10:01:00'); // 變成4天
        $reservation->updateReservation([
            'end_at' => $newEndAt,
        ]);

        $reservation->refresh();
        // 新的總金額應該是：10/09(週五1000) + 10/10(週六1200) + 10/11(週日1200) + 10/12(週一1000) = 4400
        $this->assertEquals(4400, $reservation->amount);
        $this->assertEquals(4, $reservation->rental_days);
    }

    /** @test */
    public function tenant_isolation_is_maintained_in_pricing()
    {
        // 建立另一個租戶
        $otherTenant = Tenant::factory()->create();

        // 另一個租戶的車輛有不同的定價
        $otherVehicle = Vehicle::factory()->create([
            'tenant_id' => $otherTenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 更新另一個租戶車輛的定價，而不是創建新的
        $otherVehicle->pricing()->update([
            'weekday_price' => 2000, // 不同的價格
            'weekend_price' => 2400,
            'holiday_price' => 3000,
        ]);

        // 建立原租戶的預約，應該使用原租戶的定價
        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-10 10:00:00');

        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->assertEquals(1000, $reservation->amount);
    }
}
