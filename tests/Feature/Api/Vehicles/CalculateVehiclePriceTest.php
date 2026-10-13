<?php

namespace Tests\Feature\Api\Vehicles;

use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\Holiday;
use App\Models\VehiclePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CalculateVehiclePriceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建測試租戶
        $this->tenant = Tenant::factory()->create([
            'is_public' => true,
            'slug' => 'test-tenant',
        ]);

        // 創建可用的車輛
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 創建車輛定價：平日1000，週末1500，假日2000
        VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
            'vehicle_id' => $this->vehicle->id,
            'weekday_price' => 1000,
            'weekend_price' => 1500,
            'holiday_price' => 2000,
        ]);
    }

    public function test_calculates_correct_price_for_normal_rental_period()
    {
        // 測試連續3個平日的租金計算
        // 假設這三天都是週一到週三（非假日、非週末）
        $startAt = Carbon::parse('2026-10-13 10:00:00'); // 週一
        $endAt = Carbon::parse('2026-10-16 18:00:00');   // 週四，共4天（不足24小時進位）

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', '租金試算成功')
            ->assertJsonPath('data.rental_days', 4) // 總共4天
            ->assertJsonPath('data.total_amount', 4000); // 4天 * 1000 = 4000
    }

    public function test_applies_weekend_pricing_correctly()
    {
        // 測試包含週末的租期
        // 2026-10-17是週五，10-18是週六，10-19是週日，10-20是週一
        $startAt = Carbon::parse('2026-10-17 10:00:00');
        $endAt = Carbon::parse('2026-10-20 18:00:00');

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(200);
        // 總共4天：週五(1000) + 週六(1500) + 週日(1500) + 週一(1000) = 5000
        $this->assertEquals(5000, $response->json('data.total_amount'));
        $this->assertCount(4, $response->json('data.breakdown'));
    }

    public function test_applies_holiday_pricing_with_higher_priority()
    {
        // 創建一個假日
        Holiday::create([
            'tenant_id' => $this->tenant->id,
            'name' => '測試假日',
            'date' => '2026-10-14',
        ]);

        // 租期包含這個假日（三天都是平日，只有中間那天被標記為假日）
        $startAt = Carbon::parse('2026-10-13 10:00:00');
        $endAt = Carbon::parse('2026-10-15 18:00:00');

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(200);
        // 總共3天：第一天平日(1000) + 假日(2000) + 第三天平日(1000) = 4000
        $this->assertEquals(4000, $response->json('data.total_amount'));

        // 驗證假日那天確實使用了holiday類型和價格
        $breakdown = collect($response->json('data.breakdown'));
        $holidayEntry = $breakdown->firstWhere('date', '2026-10-14');
        $this->assertEquals('holiday', $holidayEntry['type']);
        $this->assertEquals(2000, $holidayEntry['price']);
    }

    public function test_rounds_up_partial_day_to_full_day()
    {
        // 只租5小時，應該進位為1天
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDay()->setHour(15); // 僅5小時

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.rental_days', 1)
            ->assertJsonPath('data.total_amount', 1000);
    }

    public function test_rejects_rental_period_less_than_four_hours()
    {
        // 只租3小時，應該被拒絕
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDay()->setHour(13); // 僅3小時

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', '最少租用時間為4小時');
    }

    public function test_rejects_invalid_date_parameters()
    {
        // 結束時間早於開始時間
        $startAt = Carbon::now()->addDays(3);
        $endAt = Carbon::now()->addDay();

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_404_for_nonexistent_vehicle()
    {
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        // 使用不存在的車輛ID 9999
        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/9999/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', '車輛不存在');
    }

    public function test_returns_404_for_vehicle_from_another_tenant()
    {
        // 創建另一個租戶
        $anotherTenant = Tenant::factory()->create([
            'is_public' => true,
            'slug' => 'another-tenant',
        ]);

        // 在另一個租戶創建一輛車
        $anotherVehicle = Vehicle::factory()->create([
            'tenant_id' => $anotherTenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create(['tenant_id' => $anotherTenant->id, 'vehicle_id' => $anotherVehicle->id]);

        // 嘗試使用原租戶的路徑計算另一個租戶車輛的價格
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$anotherVehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(404);
    }

    public function test_returns_correct_json_types_for_monetary_values()
    {
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(200);

        // 驗證所有金額都是數字型別（不是字串）
        $this->assertIsNumeric($response->json('data.total_amount'));
        foreach ($response->json('data.breakdown') as $entry) {
            $this->assertIsNumeric($entry['price']);
        }
    }

    public function test_calculation_does_not_create_or_modify_any_data()
    {
        // 記錄計算前的預約數量
        $initialReservationCount = \App\Models\Reservation::count();
        $initialVehicleCount = \App\Models\Vehicle::count();

        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        $response = $this->postJson("/api/v1/public/{$this->tenant->slug}/vehicles/{$this->vehicle->id}/calculate-price", [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]);

        $response->assertStatus(200);

        // 驗證沒有任何新資料被創建
        $this->assertEquals($initialReservationCount, \App\Models\Reservation::count());
        $this->assertEquals($initialVehicleCount, \App\Models\Vehicle::count());
    }
}
