<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehiclePricing;
use App\Models\Customer;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RentalDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 只在本地或測試環境執行
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        $this->command->info('開始建立租車演示資料...');

        // 取得三個既有租戶：台北、台中、高雄
        $demoTenants = $this->getExistingTenants();

        foreach ($demoTenants as $tenant) {
            $this->command->info("處理租戶: {$tenant->name} (ID: {$tenant->id})");

            // 為每個租戶建立3間分店（對應不同地區）
            $branches = $this->createBranchesForTenant($tenant);
            $this->command->info("  已建立/確認分店數量: " . count($branches));

            // 建立車輛，分配到不同分店
            $vehicles = $this->createVehiclesForTenant($tenant, $branches);
            $this->command->info("  已建立/確認車輛數量: " . count($vehicles));

            // 建立客戶
            $customers = $this->createCustomersForTenant($tenant);
            $this->command->info("  已建立/確認客戶數量: " . count($customers));

            // 建立各種預約情境
            $this->createReservationsForTenant($tenant, $branches, $vehicles, $customers);
        }

        $this->command->info('演示資料建立完成！');
    }

    /**
     * 取得既有租戶（台北、台中、高雄）
     */
    private function getExistingTenants(): array
    {
        $tenants = Tenant::whereIn('slug', [
            'taipei-rental',
            'taichung-rental',
            'kaohsiung-rental'
        ])->get();

        return $tenants->all();
    }

    /**
     * 為租戶建立分店
     */
    private function createBranchesForTenant($tenant): array
    {
        $defaultBusinessHours = json_encode([
            'monday' => ['09:00', '21:00'],
            'tuesday' => ['09:00', '21:00'],
            'wednesday' => ['09:00', '21:00'],
            'thursday' => ['09:00', '21:00'],
            'friday' => ['09:00', '22:00'],
            'saturday' => ['08:00', '22:00'],
            'sunday' => ['08:00', '21:00'],
        ]);

        // 根據租戶slug建立對應地區的分店
        $branchData = match ($tenant->slug) {
            'taipei-rental' => [
                [
                    'name' => '台北車站店',
                    'address' => '台北市中正區北平西路3號',
                    'phone' => '02-23112345',
                    'is_active' => true,
                ],
                [
                    'name' => '板橋店',
                    'address' => '新北市板橋區縣民大道二段7號',
                    'phone' => '02-29667890',
                    'is_active' => true,
                ],
                [
                    'name' => '內湖店',
                    'address' => '台北市內湖區瑞光路335號',
                    'phone' => '02-87976543',
                    'is_active' => true,
                ],
            ],
            'taichung-rental' => [
                [
                    'name' => '台中車站店',
                    'address' => '台中市中區台灣大道一段1號',
                    'phone' => '04-22223333',
                    'is_active' => true,
                ],
                [
                    'name' => '西屯店',
                    'address' => '台中市西屯區台中港路三段258號',
                    'phone' => '04-27584444',
                    'is_active' => true,
                ],
                [
                    'name' => '逢甲店',
                    'address' => '台中市西屯區逢甲路20號',
                    'phone' => '04-24515555',
                    'is_active' => true,
                ],
            ],
            'kaohsiung-rental' => [
                [
                    'name' => '高雄車站店',
                    'address' => '高雄市三民區建國二路320號',
                    'phone' => '07-23456789',
                    'is_active' => true,
                ],
                [
                    'name' => '左營店',
                    'address' => '高雄市左營區博愛二路330號',
                    'phone' => '07-34567890',
                    'is_active' => true,
                ],
                [
                    'name' => '鳳山店',
                    'address' => '高雄市鳳山區中山路168號',
                    'phone' => '07-78901234',
                    'is_active' => true,
                ],
            ],
            default => [],
        };

        $branches = [];
        foreach ($branchData as $data) {
            $branch = Branch::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['name']],
                [
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'business_hours' => $defaultBusinessHours,
                    'is_active' => $data['is_active'],
                ]
            );
            $branches[$data['name']] = $branch;
        }

        return $branches;
    }

    /**
     * 為租戶建立車輛，分配到不同分店
     */
    private function createVehiclesForTenant($tenant, $branches): array
    {
        $branchList = collect($branches)->values();

        // 車型資料
        $vehicleTemplates = [
            [
                'name' => 'Toyota Yaris',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2021,
                'weekday_price' => 1200,
                'weekend_price' => 1500,
                'holiday_price' => 1800,
            ],
            [
                'name' => 'Toyota Corolla Altis',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2022,
                'weekday_price' => 1500,
                'weekend_price' => 1800,
                'holiday_price' => 2100,
            ],
            [
                'name' => 'Toyota Corolla Cross',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_HYBRID,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2023,
                'weekday_price' => 2000,
                'weekend_price' => 2400,
                'holiday_price' => 2800,
            ],
            [
                'name' => 'Toyota RAV4',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2023,
                'weekday_price' => 2500,
                'weekend_price' => 3000,
                'holiday_price' => 3500,
            ],
            [
                'name' => 'Honda HR-V',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2022,
                'weekday_price' => 1800,
                'weekend_price' => 2200,
                'holiday_price' => 2600,
            ],
            [
                'name' => 'Nissan Kicks',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_ELECTRIC,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2022,
                'weekday_price' => 1600,
                'weekend_price' => 1900,
                'holiday_price' => 2300,
            ],
            [
                'name' => 'Mazda CX-5',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_DIESEL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2020,
                'weekday_price' => 2200,
                'weekend_price' => 2600,
                'holiday_price' => 3000,
            ],
            [
                'name' => 'Toyota Sienta',
                'seats' => 7,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2022,
                'weekday_price' => 2300,
                'weekend_price' => 2700,
                'holiday_price' => 3100,
            ],
            [
                'name' => 'Honda CR-V',
                'seats' => 5,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2021,
                'weekday_price' => 2400,
                'weekend_price' => 2800,
                'holiday_price' => 3200,
            ],
            [
                'name' => 'Mitsubishi Zinger',
                'seats' => 7,
                'fuel_type' => Vehicle::FUEL_PETROL,
                'transmission' => Vehicle::TRANSMISSION_AUTOMATIC,
                'manufacture_year' => 2017,
                'weekday_price' => 1400,
                'weekend_price' => 1700,
                'holiday_price' => 2000,
            ],
        ];

        $vehicles = [];
        $plateNumberCounter = 1;
        $tenantSlug = $tenant->slug;

        foreach ($vehicleTemplates as $index => $template) {
            // 每個車型建立2台，總共20台車
            for ($copy = 0; $copy < 2; $copy++) {
                // 輪流分配到不同分店
                $branchIndex = ($index * 2 + $copy) % count($branchList);
                $currentBranch = $branchList[$branchIndex];

                // 設定狀態，最後2台設定為維修或停用來測試各種狀態
                $status = Vehicle::STATUS_AVAILABLE;
                $maintenanceStart = null;
                $maintenanceEnd = null;
                if ($index === count($vehicleTemplates) - 1 && $copy === 0) {
                    // 最後一台設定為維修中
                    $status = Vehicle::STATUS_MAINTENANCE;
                    $maintenanceStart = Carbon::now()->subDays(2);
                    $maintenanceEnd = Carbon::now()->addDays(3);
                } elseif ($index === count($vehicleTemplates) - 1 && $copy === 1) {
                    // 倒數第二台設定為停用
                    $status = Vehicle::STATUS_INACTIVE;
                }

                // 產生唯一車牌
                $plateSuffix = str_pad($plateNumberCounter++, 4, '0', STR_PAD_LEFT);
                $plateNumber = "demo-{$tenantSlug}-{$plateSuffix}";

                $vehicle = Vehicle::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'plate_number' => $plateNumber],
                    [
                        'current_branch_id' => $currentBranch->id,
                        'name' => $template['name'],
                        'seats' => $template['seats'],
                        'status' => $status,
                        'description' => "{$template['name']} - 演示用車輛",
                        'manufacture_year' => $template['manufacture_year'],
                        'mileage' => rand(30000, 150000),
                        'fuel_type' => $template['fuel_type'],
                        'transmission' => $template['transmission'],
                        'maintenance_start_at' => $maintenanceStart,
                        'maintenance_expected_end_at' => $maintenanceEnd,
                    ]
                );
                $vehicles[] = $vehicle;

                // 建立價格資料
                VehiclePricing::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle->id],
                    [
                        'weekday_price' => $template['weekday_price'],
                        'weekend_price' => $template['weekend_price'],
                        'holiday_price' => $template['holiday_price'],
                    ]
                );
            }
        }

        return $vehicles;
    }

    /**
     * 為租戶建立客戶
     */
    private function createCustomersForTenant($tenant): array
    {
        $customerData = [
            [
                'name' => '演示客戶一',
                'email_suffix' => 'demo1',
                'id_number' => 'A123456789',
                'license_number' => 'AA12345678',
                'phone' => '0912111111',
            ],
            [
                'name' => '演示客戶二',
                'email_suffix' => 'demo2',
                'id_number' => 'B234567890',
                'license_number' => 'BB23456789',
                'phone' => '0912222222',
            ],
            [
                'name' => '演示客戶三',
                'email_suffix' => 'demo3',
                'id_number' => 'C345678901',
                'license_number' => 'CC34567890',
                'phone' => '0912333333',
            ],
            [
                'name' => '演示客戶四',
                'email_suffix' => 'demo4',
                'id_number' => 'D456789012',
                'license_number' => 'DD45678901',
                'phone' => '0912444444',
            ],
            [
                'name' => '演示客戶五',
                'email_suffix' => 'demo5',
                'id_number' => 'E567890123',
                'license_number' => 'EE56789012',
                'phone' => '0912555555',
            ],
        ];

        $customers = [];
        $tenantSlug = $tenant->slug;

        foreach ($customerData as $data) {
            $email = "{$data['email_suffix']}@{$tenantSlug}.demo";

            $customer = Customer::updateOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $email],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'id_number' => $data['id_number'],
                    'license_number' => $data['license_number'],
                    'license_expiry' => '2035-12-31',
                    'address' => '台北市演示區演示路100號',
                    'emergency_contact_name' => '緊急聯絡人',
                    'emergency_contact_phone' => '0900000000',
                    'is_active' => true,
                ]
            );
            $customers[] = $customer;
        }

        return $customers;
    }

    /**
     * 建立各種預約情境
     */
    private function createReservationsForTenant($tenant, $branches, $vehicles, $customers): void
    {
        $today = Carbon::today();
        $availableVehicles = collect($vehicles)->where('status', Vehicle::STATUS_AVAILABLE);

        if ($availableVehicles->isEmpty() || empty($customers)) {
            return;
        }

        // 根據租戶slug取得對應的三個分店
        $branchKeys = match ($tenant->slug) {
            'taipei-rental' => ['台北車站店', '板橋店', '內湖店'],
            'taichung-rental' => ['台中車站店', '西屯店', '逢甲店'],
            'kaohsiung-rental' => ['高雄車站店', '左營店', '鳳山店'],
            default => [],
        };

        if (empty($branchKeys)) {
            return;
        }

        $mainBranch = $branches[$branchKeys[0]] ?? null;
        $secondBranch = $branches[$branchKeys[1]] ?? null;
        $thirdBranch = $branches[$branchKeys[2]] ?? null;

        if (!$mainBranch || !$secondBranch || !$thirdBranch) {
            return;
        }

        $customerPool = collect($customers);

        // ========== 情境C：同店取還車 ==========
        $this->createSameBranchReservations($tenant, $availableVehicles, $customerPool, $mainBranch, $secondBranch, $today);

        // ========== 情境D：跨店還車 ==========
        $this->createCrossBranchReservations($tenant, $availableVehicles, $customerPool, $mainBranch, $secondBranch, $thirdBranch, $today);

        // ========== 建立一個目前租用中的預約（狀態PICKED_UP） ==========
        $this->createActiveRental($tenant, $availableVehicles, $customerPool, $mainBranch, $secondBranch, $today);
    }

    /**
     * 建立同店取還車預約
     */
    private function createSameBranchReservations($tenant, $availableVehicles, $customerPool, $mainBranch, $secondBranch, $today): void
    {
        // 主要據點取車、主要據點還車 - 未來3天
        $vehicle = $availableVehicles->where('current_branch_id', $mainBranch->id)->first();
        if ($vehicle) {
            $startAt = $today->copy()->addDays(3)->setTime(9, 0);
            $endAt = $startAt->copy()->addDays(2)->setTime(18, 0);
            $customer = $customerPool->random();

            $this->tryCreateReservation($tenant, $customer, $vehicle, $mainBranch->id, $mainBranch->id, $startAt, $endAt, Reservation::STATUS_CONFIRMED);
        }

        // 第二個據點取車、第二個據點還車 - 未來7天
        $vehicle2 = $availableVehicles->where('current_branch_id', $secondBranch->id)->first();
        if ($vehicle2) {
            $startAt = $today->copy()->addDays(7)->setTime(10, 0);
            $endAt = $startAt->copy()->addDays(3)->setTime(17, 0);
            $customer = $customerPool->random();

            $this->tryCreateReservation($tenant, $customer, $vehicle2, $secondBranch->id, $secondBranch->id, $startAt, $endAt, Reservation::STATUS_PENDING);
        }

        // 相同車輛的相鄰時段 - 測試半開區間 [start_at, end_at)
        $vehicle3 = $availableVehicles->where('current_branch_id', $mainBranch->id)->skip(1)->first();
        if ($vehicle3) {
            // 第一個預約：今天+10天 09:00 取車，今天+12天 18:00 還車
            $start1 = $today->copy()->addDays(10)->setTime(9, 0);
            $end1 = $start1->copy()->addDays(2)->setTime(18, 0);
            $customer1 = $customerPool->random();
            $this->tryCreateReservation($tenant, $customer1, $vehicle3, $mainBranch->id, $mainBranch->id, $start1, $end1, Reservation::STATUS_CONFIRMED);

            // 第二個預約：同一天的18:00可以接著用，驗證半開區間邏輯
            $start2 = $end1->copy(); // 直接使用前一個的結束時間作為下一個的開始
            $end2 = $start2->copy()->addDays(3)->setTime(12, 0);
            $customer2 = $customerPool->random();
            $this->tryCreateReservation($tenant, $customer2, $vehicle3, $mainBranch->id, $mainBranch->id, $start2, $end2, Reservation::STATUS_CONFIRMED);
        }
    }

    /**
     * 建立跨店還車預約
     */
    private function createCrossBranchReservations($tenant, $availableVehicles, $customerPool, $mainBranch, $secondBranch, $thirdBranch, $today): void
    {
        // 主要據點取車、第二個據點還車 - 未來5天
        $vehicle = $availableVehicles->where('current_branch_id', $mainBranch->id)->first();
        if ($vehicle) {
            $startAt = $today->copy()->addDays(5)->setTime(9, 0);
            $endAt = $startAt->copy()->addDays(2)->setTime(18, 0);
            $customer = $customerPool->random();

            $this->tryCreateReservation($tenant, $customer, $vehicle, $mainBranch->id, $secondBranch->id, $startAt, $endAt, Reservation::STATUS_CONFIRMED);
        }

        // 第二個據點取車、第三個據點還車 - 未來10天
        $vehicle2 = $availableVehicles->where('current_branch_id', $secondBranch->id)->first();
        if ($vehicle2) {
            $startAt = $today->copy()->addDays(10)->setTime(8, 0);
            $endAt = $startAt->copy()->addDays(1)->setTime(20, 0);
            $customer = $customerPool->random();

            $this->tryCreateReservation($tenant, $customer, $vehicle2, $secondBranch->id, $thirdBranch->id, $startAt, $endAt, Reservation::STATUS_CONFIRMED);
        }
    }

    /**
     * 建立目前正在租用中的預約
     */
    private function createActiveRental($tenant, $availableVehicles, $customerPool, $mainBranch, $secondBranch, $today): void
    {
        $vehicle = $availableVehicles->where('current_branch_id', $mainBranch->id)->skip(2)->first();
        if ($vehicle) {
            // 昨天取車，明天還車，目前狀態為PICKED_UP（租用中）
            $startAt = $today->copy()->subDay()->setTime(9, 0);
            $endAt = $today->copy()->addDay()->setTime(18, 0);
            $customer = $customerPool->random();

            $this->tryCreateReservation($tenant, $customer, $vehicle, $mainBranch->id, $secondBranch->id, $startAt, $endAt, Reservation::STATUS_PICKED_UP);
        }
    }

    /**
     * 嘗試建立預約，捕捉例外避免中斷執行
     */
    private function tryCreateReservation($tenant, $customer, $vehicle, $pickupBranchId, $returnBranchId, $startAt, $endAt, $status): void
    {
        try {
            // 先檢查是否已存在類似的預約，避免重複建立
            $exists = Reservation::where('tenant_id', $tenant->id)
                ->where('vehicle_id', $vehicle->id)
                ->where('start_at', $startAt)
                ->where('end_at', $endAt)
                ->exists();

            if (!$exists) {
                Reservation::createReservation([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'pickup_branch_id' => $pickupBranchId,
                    'return_branch_id' => $returnBranchId,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => $status,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("建立預約時發生錯誤: " . $e->getMessage());
            // 不丟出例外，讓seeder繼續執行
        }
    }
}
