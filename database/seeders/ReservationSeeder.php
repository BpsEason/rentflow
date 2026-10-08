<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $customers = Customer::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            $vehicles = Vehicle::where('tenant_id', $tenant->id)
                ->where('status', 'AVAILABLE')
                ->get();

            if ($customers->isEmpty() || $vehicles->isEmpty()) {
                continue;
            }

            $this->seedReservationsForTenant($tenant, $customers, $vehicles);
        }
    }

    private function seedReservationsForTenant($tenant, $customers, $vehicles): void
    {
        $now = Carbon::now()->startOfDay();
        $today = Carbon::today();

        // 為不同車型設定不同的使用率 - 模擬高/中/低使用率
        $usageRates = $this->getVehicleUsageRates($vehicles);

        // 建立未來7天的出租率曲線，讓不同日期有不同的需求量
        $futureWeekOccupancyTarget = [
            1 => 40,  // 明天: 40%
            2 => 60,  // 後天: 60%
            3 => 80,  // 第3天: 80%
            4 => 95,  // 第4天: 95% (需求高峰)
            5 => 70,  // 第5天: 70%
            6 => 50,  // 第6天: 50%
            7 => 30,  // 第7天: 30%
        ];

        // 第一步：建立過去30天的預約 (40%)
        $this->createPastReservations($tenant, $customers, $vehicles, $usageRates, $now);

        // 第二步：建立今天的預約 (10%) - 包含今日取車和今日還車
        $this->createTodayReservations($tenant, $customers, $vehicles, $today);

        // 第三步：建立未來7天的預約 (20%) - 按照不同的出租率目標
        $this->createFutureWeekReservations($tenant, $customers, $vehicles, $today, $futureWeekOccupancyTarget);

        // 第四步：建立未來8-30天的預約 (30%)
        $this->createFutureMonthReservations($tenant, $customers, $vehicles, $today);
    }

    /**
     * 為每台車設定使用率權重，創造高低使用率的差異
     */
    private function getVehicleUsageRates($vehicles): array
    {
        $rates = [];
        foreach ($vehicles as $vehicle) {
            // 高使用率車型: Toyota Yaris, Toyota Corolla Altis (熱門車款)
            if (in_array($vehicle->name, ['Toyota Yaris', 'Toyota Corolla Altis'])) {
                $rates[$vehicle->id] = 0.9;  // 90% 機會被預約
            }
            // 中使用率車型: Honda HR-V, Mazda CX-5, Toyota RAV4, Nissan Kicks
            elseif (in_array($vehicle->name, ['Honda HR-V', 'Mazda CX-5', 'Toyota RAV4', 'Nissan Kicks', 'Toyota Corolla Cross'])) {
                $rates[$vehicle->id] = 0.6;  // 60% 機會被預約
            }
            // 低使用率車型: 其他車款
            else {
                $rates[$vehicle->id] = 0.3;  // 30% 機會被預約
            }
        }
        return $rates;
    }

    /**
     * 建立過去30天的預約
     */
    private function createPastReservations($tenant, $customers, $vehicles, $usageRates, $now): void
    {
        $customerPool = $customers->shuffle();

        // 過去30天，每天隨機建立一些預約
        for ($dayOffset = 30; $dayOffset >= 1; $dayOffset--) {
            $date = $now->copy()->subDays($dayOffset);

            foreach ($vehicles as $vIndex => $vehicle) {
                // 根據使用率決定是否建立預約
                if (mt_rand() / mt_getrandmax() > $usageRates[$vehicle->id]) {
                    continue;
                }

                // 隨機租用1-3天
                $rentalDays = rand(1, 3);
                $startAt = $date->copy()->setTime(9, 0);  // 早上9點取車
                $endAt = $startAt->copy()->addDays($rentalDays)->setTime(18, 0);  // 晚上6點還車

                // 檢查時間衝突，如果有衝突就跳過
                if (Reservation::hasTimeConflict($vehicle->id, $startAt, $endAt)) {
                    continue;
                }

                // 隨機分配狀態：大部分是RETURNED，少量CANCELLED
                $status = rand(0, 10) < 9 ? Reservation::STATUS_RETURNED : Reservation::STATUS_CANCELLED;

                // 少量逾期未還的狀況（很早以前的預約但還是PICKED_UP）
                if ($dayOffset > 7 && rand(0, 20) === 0) {
                    $status = Reservation::STATUS_PICKED_UP;
                }

                $customer = $customerPool->random();

                try {
                    Reservation::createReservation([
                        'tenant_id' => $tenant->id,
                        'customer_id' => $customer->id,
                        'vehicle_id' => $vehicle->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'status' => $status,
                    ]);
                } catch (\Exception $e) {
                    // 忽略衝突錯誤
                    continue;
                }
            }
        }
    }

    /**
     * 建立今天的預約 - 確保Dashboard有今日取車、今日還車的資料
     */
    private function createTodayReservations($tenant, $customers, $vehicles, $today): void
    {
        $customerPool = $customers->shuffle();

        // 建立數筆今日取車的預約
        $todayPickupCount = min(3, count($vehicles));
        $usedVehicles = collect();

        for ($i = 0; $i < $todayPickupCount; $i++) {
            $availableVehicles = $vehicles->whereNotIn('id', $usedVehicles);
            if ($availableVehicles->isEmpty()) break;

            $vehicle = $availableVehicles->random();
            $usedVehicles->push($vehicle->id);

            $startAt = $today->copy()->setTime(9, 0);
            $endAt = $today->copy()->addDays(2)->setTime(18, 0);

            $customer = $customerPool->random();

            try {
                Reservation::createReservation([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => rand(0, 1) ? Reservation::STATUS_CONFIRMED : Reservation::STATUS_PICKED_UP,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }

        // 建立數筆今日還車的預約（昨天取車，今天還）
        $todayReturnCount = min(2, count($vehicles) - $usedVehicles->count());
        for ($i = 0; $i < $todayReturnCount; $i++) {
            $availableVehicles = $vehicles->whereNotIn('id', $usedVehicles);
            if ($availableVehicles->isEmpty()) break;

            $vehicle = $availableVehicles->random();
            $usedVehicles->push($vehicle->id);

            $startAt = $today->copy()->subDay()->setTime(10, 0);
            $endAt = $today->copy()->setTime(18, 0);  // 今天還車

            $customer = $customerPool->random();

            try {
                Reservation::createReservation([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => Reservation::STATUS_PICKED_UP,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }

        // 確保有一些PENDING的待確認預約
        $pendingCount = min(4, count($vehicles) - $usedVehicles->count());
        for ($i = 0; $i < $pendingCount; $i++) {
            $availableVehicles = $vehicles->whereNotIn('id', $usedVehicles);
            if ($availableVehicles->isEmpty()) break;

            $vehicle = $availableVehicles->random();
            $usedVehicles->push($vehicle->id);

            $startAt = $today->copy()->addDays(3)->setTime(9, 0);
            $endAt = $startAt->copy()->addDays(2)->setTime(18, 0);

            $customer = $customerPool->random();

            try {
                Reservation::createReservation([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => Reservation::STATUS_PENDING,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * 建立未來7天的預約，按照不同的出租率目標
     */
    private function createFutureWeekReservations($tenant, $customers, $vehicles, $today, $occupancyTargets): void
    {
        $customerPool = $customers->shuffle();
        $availableVehicles = $vehicles->keyBy('id');

        foreach ($occupancyTargets as $dayOffset => $targetRate) {
            $targetDate = $today->copy()->addDays($dayOffset);
            $neededReservations = (int)(count($availableVehicles) * $targetRate / 100);
            $createdCount = 0;

            // 隨機選擇車輛來達到目標出租率
            $shuffledVehicles = $availableVehicles->shuffle();

            foreach ($shuffledVehicles as $vehicle) {
                if ($createdCount >= $neededReservations) break;

                // 隨機租用1-2天
                $rentalDays = rand(1, 2);
                $startAt = $targetDate->copy()->setTime(10, 0);
                $endAt = $startAt->copy()->addDays($rentalDays)->setTime(17, 0);

                if (Reservation::hasTimeConflict($vehicle->id, $startAt, $endAt)) {
                    continue;
                }

                // 未來的預約主要是PENDING或CONFIRMED，少量CANCELLED
                $rand = rand(0, 100);
                if ($rand < 60) {
                    $status = Reservation::STATUS_CONFIRMED;
                } elseif ($rand < 85) {
                    $status = Reservation::STATUS_PENDING;
                } else {
                    $status = Reservation::STATUS_CANCELLED;
                }

                $customer = $customerPool->random();

                try {
                    Reservation::createReservation([
                        'tenant_id' => $tenant->id,
                        'customer_id' => $customer->id,
                        'vehicle_id' => $vehicle->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'status' => $status,
                    ]);
                    $createdCount++;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * 建立未來8-30天的預約
     */
    private function createFutureMonthReservations($tenant, $customers, $vehicles, $today): void
    {
        $customerPool = $customers->shuffle();

        // 未來30天內，隨機建立預約
        for ($dayOffset = 8; $dayOffset <= 30; $dayOffset++) {
            $date = $today->copy()->addDays($dayOffset);

            // 每天隨機建立一些預約
            $dailyReservations = rand(2, 6);

            for ($i = 0; $i < $dailyReservations; $i++) {
                $vehicle = $vehicles->random();

                // 隨機租用1-4天
                $rentalDays = rand(1, 4);
                $startAt = $date->copy()->setTime(9, 0);
                $endAt = $startAt->copy()->addDays($rentalDays)->setTime(18, 0);

                if (Reservation::hasTimeConflict($vehicle->id, $startAt, $endAt)) {
                    continue;
                }

                // 越遙遠的未來，PENDING的比例越高
                $rand = rand(0, 100);
                if ($dayOffset > 20) {
                    // 很久以後的預約以PENDING為主
                    $status = $rand < 70 ? Reservation::STATUS_PENDING : ($rand < 90 ? Reservation::STATUS_CONFIRMED : Reservation::STATUS_CANCELLED);
                } else {
                    $status = $rand < 40 ? Reservation::STATUS_CONFIRMED : ($rand < 75 ? Reservation::STATUS_PENDING : Reservation::STATUS_CANCELLED);
                }

                $customer = $customerPool->random();

                try {
                    Reservation::createReservation([
                        'tenant_id' => $tenant->id,
                        'customer_id' => $customer->id,
                        'vehicle_id' => $vehicle->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'status' => $status,
                    ]);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
}
