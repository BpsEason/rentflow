<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\VehiclePricing;
use App\Models\Holiday;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PricingService $pricingService;
    protected Tenant $tenant;
    protected Vehicle $vehicle;
    protected VehiclePricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingService = new PricingService();

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
            'weekday_price' => 1000, // 平日 1000
            'weekend_price' => 1200, // 週末 1200
            'holiday_price' => 1500, // 假日 1500
        ]);
        $this->pricing = $this->vehicle->pricing;
    }

    /** @test */
    public function it_calculates_rental_days_correctly()
    {
        // 4小時應該計算為1天
        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-09 14:00:00');
        $this->assertEquals(1, $this->pricingService->calculateRentalDays($startAt, $endAt));

        // 23小時應該計算為1天
        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-10 09:00:00');
        $this->assertEquals(1, $this->pricingService->calculateRentalDays($startAt, $endAt));

        // 24小時應該計算為1天
        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-10 10:00:00');
        $this->assertEquals(1, $this->pricingService->calculateRentalDays($startAt, $endAt));

        // 24小時1分鐘應該計算為2天
        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-10 10:01:00');
        $this->assertEquals(2, $this->pricingService->calculateRentalDays($startAt, $endAt));
    }

    /** @test */
    public function it_applies_weekday_price_correctly()
    {
        // 2026-10-09 是週四（weekday）
        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-10 10:00:00');

        $result = $this->pricingService->calculateTotalAmount($this->vehicle, $startAt, $endAt);

        $this->assertEquals(1, $result['rental_days']);
        $this->assertEquals(1000, $result['amount']);
    }

    /** @test */
    public function it_applies_weekend_price_correctly()
    {
        // 2026-10-11 是週六（weekend）
        $startAt = Carbon::parse('2026-10-11 10:00:00');
        $endAt = Carbon::parse('2026-10-12 10:00:00');

        $result = $this->pricingService->calculateTotalAmount($this->vehicle, $startAt, $endAt);

        $this->assertEquals(1, $result['rental_days']);
        $this->assertEquals(1200, $result['amount']);
    }

    /** @test */
    public function it_applies_holiday_price_correctly()
    {
        // 建立一個假日
        Holiday::create([
            'tenant_id' => $this->tenant->id,
            'date' => '2026-10-10',
            'name' => '國慶日',
        ]);

        $startAt = Carbon::parse('2026-10-10 10:00:00');
        $endAt = Carbon::parse('2026-10-11 10:00:00');

        $result = $this->pricingService->calculateTotalAmount($this->vehicle, $startAt, $endAt);

        $this->assertEquals(1, $result['rental_days']);
        $this->assertEquals(1500, $result['amount']);
    }

    /** @test */
    public function holiday_takes_precedence_over_weekend()
    {
        // 2026-10-11 是週六，同時設為假日
        Holiday::create([
            'tenant_id' => $this->tenant->id,
            'date' => '2026-10-11',
            'name' => '測試假日',
        ]);

        $startAt = Carbon::parse('2026-10-11 10:00:00');
        $endAt = Carbon::parse('2026-10-12 10:00:00');

        $result = $this->pricingService->calculateTotalAmount($this->vehicle, $startAt, $endAt);

        // 即使是週末，假日價格仍然優先
        $this->assertEquals(1, $result['rental_days']);
        $this->assertEquals(1500, $result['amount']);
    }

    /** @test */
    public function it_calculates_cross_date_pricing_correctly()
    {
        // 跨工作日到週末：10/09(四) -> 10/12(日)
        // 10/09: 平日 1000
        // 10/10: 週五 1000
        // 10/11: 週六 1200
        // 總共租用3天，總金額 1000 + 1000 + 1200 = 3200

        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-12 10:01:00'); // 3天1分鐘 -> 4天？不，這裡是72小時1分鐘，應該是4天

        $result = $this->pricingService->calculateTotalAmount($this->vehicle, $startAt, $endAt);

        $this->assertEquals(4, $result['rental_days']);
        // 10/09: 1000, 10/10: 1000, 10/11: 1200, 10/12: 1200(週日) = 4400
        $this->assertEquals(4400, $result['amount']);
    }

    /** @test */
    public function it_throws_exception_when_vehicle_has_no_pricing()
    {
        // 建立一個車輛然後刪除它的定價，因為 VehicleFactory 會自動創建定價
        $newVehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'New Vehicle Without Pricing',
        ]);
        // 刪除自動創建的定價，讓車輛沒有定價
        $newVehicle->pricing()->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('此車輛尚未設定價格');

        $startAt = Carbon::parse('2026-10-09 10:00:00');
        $endAt = Carbon::parse('2026-10-10 10:00:00');

        $this->pricingService->calculateTotalAmount($newVehicle, $startAt, $endAt);
    }
}
