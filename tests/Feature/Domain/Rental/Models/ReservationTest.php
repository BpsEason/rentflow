<?php

namespace Tests\Feature\Domain\Rental\Models;

use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehiclePricing;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $customer;
    private $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'AVAILABLE'
        ]);
        // 更新現有的定價，而不是創建新的，因為 VehicleFactory 已經自動創建了
        $this->vehicle->pricing()->update([
            'weekday_price' => 1000,
            'weekend_price' => 1200,
            'holiday_price' => 1500,
        ]);
    }

    /** @test */
    public function it_requires_minimum_4_hours_rental_time()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('最少租用時間為4小時');

        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = Carbon::now()->setTime(13, 59, 0);

        Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
    }

    /** @test */
    public function it_allows_4_hours_rental_time()
    {
        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = Carbon::now()->setTime(14, 0, 0);

        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals(1, $reservation->rental_days);
    }

    /** @test */
    public function it_calculates_rental_days_correctly()
    {
        // 23小時應該算1天
        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = (clone $startAt)->addHours(23);
        $this->assertEquals(1, Reservation::calculateRentalDays($startAt, $endAt));

        // 24小時1分鐘應該算2天
        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = (clone $startAt)->addHours(24)->addMinute(1);
        $this->assertEquals(2, Reservation::calculateRentalDays($startAt, $endAt));
    }

    /** @test */
    public function it_detects_time_conflicts()
    {
        // 建立第一個預約
        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = (clone $startAt)->addHours(4);
        Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        // 嘗試建立重疊的預約
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('指定時間區間內車輛已被預約');

        $conflictingStart = (clone $startAt)->addHours(3);
        $conflictingEnd = (clone $conflictingStart)->addHours(4);
        Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $conflictingStart,
            'end_at' => $conflictingEnd,
        ]);
    }

    /** @test */
    public function it_allows_back_to_back_reservations()
    {
        // 第一個預約 10:00-14:00
        $startAt1 = Carbon::now()->setTime(10, 0, 0);
        $endAt1 = (clone $startAt1)->addHours(4);
        $reservation1 = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt1,
            'end_at' => $endAt1,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        // 第二個預約 14:00-18:00 - 應該成功
        $startAt2 = Carbon::now()->setTime(14, 0, 0);
        $endAt2 = (clone $startAt2)->addHours(4);
        $reservation2 = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt2,
            'end_at' => $endAt2,
        ]);

        $this->assertCount(2, Reservation::all());
    }

    /** @test */
    public function returned_reservations_do_not_block_new_reservations()
    {
        // 建立一個已歸還的預約
        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = (clone $startAt)->addHours(4);
        Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => Reservation::STATUS_RETURNED,
        ]);

        // 在相同時間建立新預約 - 應該成功
        $newStart = Carbon::now()->setTime(10, 0, 0);
        $newEnd = (clone $newStart)->addHours(4);
        $newReservation = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $newStart,
            'end_at' => $newEnd,
        ]);

        $this->assertInstanceOf(Reservation::class, $newReservation);
    }

    /** @test */
    public function it_prevents_concurrent_reservations_for_same_vehicle()
    {
        $startAt = Carbon::now()->addDays(10)->setTime(10, 0, 0);
        $endAt = (clone $startAt)->addHours(4);
        $successCount = 0;
        $failureCount = 0;

        // 模擬5個並發請求
        for ($i = 0; $i < 5; $i++) {
            try {
                DB::transaction(function () use ($startAt, $endAt) {
                    return Reservation::createReservation([
                        'tenant_id' => $this->tenant->id,
                        'customer_id' => $this->customer->id,
                        'vehicle_id' => $this->vehicle->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                    ]);
                });
                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
            }
        }

        $this->assertEquals(1, $successCount);
        $this->assertEquals(4, $failureCount);
        $this->assertCount(1, Reservation::all());
    }

    /** @test */
    public function it_allows_updating_reservation_without_conflicting_with_itself()
    {
        $startAt = Carbon::now()->setTime(10, 0, 0);
        $endAt = (clone $startAt)->addHours(4);
        $reservation = Reservation::createReservation([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        // 更新同一個預約的其他欄位 - 應該成功
        $updated = $reservation->updateReservation([
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->assertEquals(Reservation::STATUS_CONFIRMED, $updated->status);
    }

    /** @test */
    public function it_calculates_pricing_correctly_for_weekdays()
    {
        // 選擇一個週一
        $startAt = Carbon::create(2026, 10, 13, 10, 0, 0); // 這是一個星期一
        $endAt = (clone $startAt)->addHours(4);

        $pricingData = Reservation::calculateAmount($this->vehicle, $startAt, $endAt);
        $this->assertEquals(1, $pricingData['rental_days']);
        $this->assertEquals(1000, $pricingData['amount']);
    }
}
