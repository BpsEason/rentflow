<?php

namespace Tests\Feature\Api\Vehicles;

use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\Reservation;
use App\Models\VehiclePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AvailableVehiclesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建測試租戶
        $this->tenant = Tenant::factory()->create([
            'is_public' => true,
            'slug' => 'test-tenant',
        ]);

        // 創建一個客戶，用於創建預約，避免硬編碼customer_id=1導致外鍵約束錯誤
        $this->customer = \App\Models\Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_get_available_vehicles_with_valid_rental_period()
    {
        // 創建兩輛可用的車輛
        $vehicle1 = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $vehicle2 = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 為兩輛車創建定價
        VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
            'vehicle_id' => $vehicle1->id,
            'weekday_price' => 1000,
        ]);
        VehiclePricing::factory()->create([
            'tenant_id' => $this->tenant->id,
            'vehicle_id' => $vehicle2->id,
            'weekday_price' => 1200,
        ]);

        // 查詢未來3天可用的車輛
        $startAt = Carbon::now()->addDay()->setHour(10)->setMinute(0);
        $endAt = Carbon::now()->addDays(3)->setHour(18)->setMinute(0);

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        // 輸出詳細響應內容用於調試
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Content: " . $response->getContent() . "\n";

        $response->assertStatus(200)
            ->assertJsonPath('message', '取得可用車輛列表成功')
            ->assertJsonCount(2, 'data'); // 應該返回兩輛車
    }

    public function test_rejects_rental_period_less_than_four_hours()
    {
        // 嘗試只租3小時
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDay()->setHour(13); // 僅3小時

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        $response->assertStatus(422)
            ->assertJsonPath('message', '最少租用時間為4小時');
    }

    public function test_rejects_invalid_date_parameters()
    {
        // 結束時間早於開始時間
        $startAt = Carbon::now()->addDays(3);
        $endAt = Carbon::now()->addDay();

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        $response->assertStatus(422); // Laravel的驗證會直接拒絕
    }

    public function test_excludes_vehicles_that_have_conflicting_reservations()
    {
        // 創建兩輛車
        $vehicle1 = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);
        $vehicle2 = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);

        // 為兩輛車創建定價
        VehiclePricing::factory()->create(['tenant_id' => $this->tenant->id, 'vehicle_id' => $vehicle1->id]);
        VehiclePricing::factory()->create(['tenant_id' => $this->tenant->id, 'vehicle_id' => $vehicle2->id]);

        // 為第一輛車創建一個已確認的預約
        Reservation::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle1->id,
            'start_at' => Carbon::now()->addDay()->setHour(8),
            'end_at' => Carbon::now()->addDays(4)->setHour(18),
            'status' => Reservation::STATUS_CONFIRMED,
            'amount' => 4000,
            'rental_days' => 4,
        ]);

        // 查詢與該預約重疊的時間區間
        $startAt = Carbon::now()->addDays(2)->setHour(10);
        $endAt = Carbon::now()->addDays(5)->setHour(18);

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // 只有第二輛車可用
    }

    public function test_allows_back_to_back_reservations_when_end_equals_next_start()
    {
        // 創建一輛車
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create(['tenant_id' => $this->tenant->id, 'vehicle_id' => $vehicle->id]);

        // 創建第一個預約：10/14 10:00 到 10/16 18:00
        Reservation::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => Carbon::parse('2026-10-14 10:00:00'),
            'end_at' => Carbon::parse('2026-10-16 18:00:00'),
            'status' => Reservation::STATUS_CONFIRMED,
            'amount' => 3000,
            'rental_days' => 3,
        ]);

        // 嘗試查詢從前一個預約結束時間開始的新預約
        $startAt = Carbon::parse('2026-10-16 18:00:00'); // 與前一個結束時間完全相同
        $endAt = Carbon::parse('2026-10-18 18:00:00');

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // 車輛應該可用，因為半開區間不衝突
    }

    public function test_pending_reservations_block_vehicle_availability()
    {
        // 創建一輛車
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create(['tenant_id' => $this->tenant->id, 'vehicle_id' => $vehicle->id]);

        // 創建一個PENDING狀態的預約
        Reservation::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => Carbon::now()->addDay()->setHour(10),
            'end_at' => Carbon::now()->addDays(3)->setHour(18),
            'status' => Reservation::STATUS_PENDING, // PENDING狀態也會佔用庫存
            'amount' => 3000,
            'rental_days' => 3,
        ]);

        // 查詢同一時間區間
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data'); // 車輛不可用，因為PENDING預約也會佔用
    }

    public function test_vehicles_from_other_tenants_are_not_included()
    {
        // 創建另一個租戶
        $anotherTenant = Tenant::factory()->create([
            'is_public' => true,
            'slug' => 'another-tenant',
        ]);

        // 在原租戶創建一輛車
        $vehicleInCurrentTenant = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create(['tenant_id' => $this->tenant->id, 'vehicle_id' => $vehicleInCurrentTenant->id]);

        // 在另一個租戶也創建一輛車
        $vehicleInAnotherTenant = Vehicle::factory()->create([
            'tenant_id' => $anotherTenant->id,
            'status' => 'AVAILABLE',
        ]);
        VehiclePricing::factory()->create(['tenant_id' => $anotherTenant->id, 'vehicle_id' => $vehicleInAnotherTenant->id]);

        // 查詢原租戶的可用車輛
        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // 只返回原租戶的車輛
    }

    public function test_response_includes_correct_pagination_structure()
    {
        // 創建20輛車來測試分頁
        for ($i = 0; $i < 20; $i++) {
            $vehicle = Vehicle::factory()->create([
                'tenant_id' => $this->tenant->id,
                'status' => 'AVAILABLE',
            ]);
            VehiclePricing::factory()->create(['tenant_id' => $this->tenant->id, 'vehicle_id' => $vehicle->id]);
        }

        $startAt = Carbon::now()->addDay()->setHour(10);
        $endAt = Carbon::now()->addDays(3)->setHour(18);

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/vehicles/available?start_at={$startAt->toDateTimeString()}&end_at={$endAt->toDateTimeString()}&page=1");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ])
            ->assertJsonPath('meta.per_page', 15) // 每頁15筆
            ->assertJsonPath('meta.total', 20); // 總共20筆
    }
}
