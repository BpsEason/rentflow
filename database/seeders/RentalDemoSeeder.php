<?php

namespace Database\Seeders;

use App\Domain\Rental\Enums\VehicleType;
use App\Domain\Rental\Enums\VehicleStatus;
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
            if (empty($vehicles)) {
                continue;
            }

            // 查詢租戶現有客戶
            $customers = Customer::where('tenant_id', $tenant->id)->get();
            if ($customers->isEmpty()) {
                $this->command->warn("  租戶 {$tenant->name} 沒有任何客戶，將略過預約建立");
                continue;
            }
            $this->command->info("  找到 {$customers->count()} 位既有客戶");

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
     * 為租戶建立車輛
     */
    private function createVehiclesForTenant($tenant, $branches): array
    {
        // 定義要建立的車輛資料
        $vehicleTemplates = [
            // 台北車站店
            ['branch_name' => '台北車站店', 'brand' => 'Toyota', 'model' => 'Camry', 'plate_number' => 'ABC-123', 'year' => 2023],
            ['branch_name' => '台北車站店', 'brand' => 'Honda', 'model' => 'CR-V', 'plate_number' => 'DEF-456', 'year' => 2024],
            // 板橋店
            ['branch_name' => '板橋店', 'brand' => 'Nissan', 'model' => 'X-Trail', 'plate_number' => 'GHI-789', 'year' => 2023],
            // 內湖店
            ['branch_name' => '內湖店', 'brand' => 'Mazda', 'model' => 'CX-5', 'plate_number' => 'JKL-012', 'year' => 2024],
            // 台中車站店
            ['branch_name' => '台中車站店', 'brand' => 'Toyota', 'model' => 'RAV4', 'plate_number' => 'MNO-345', 'year' => 2023],
            // 西屯店
            ['branch_name' => '西屯店', 'brand' => 'Honda', 'model' => 'Accord', 'plate_number' => 'PQR-678', 'year' => 2024],
            // 逢甲店
            ['branch_name' => '逢甲店', 'brand' => 'Ford', 'model' => 'Kuga', 'plate_number' => 'STU-901', 'year' => 2023],
            // 高雄車站店
            ['branch_name' => '高雄車站店', 'brand' => 'Toyota', 'model' => 'Corolla Cross', 'plate_number' => 'VWX-234', 'year' => 2024],
            // 左營店
            ['branch_name' => '左營店', 'brand' => 'Mercedes-Benz', 'model' => 'C-Class', 'plate_number' => 'YZA-567', 'year' => 2023],
            // 鳳山店
            ['branch_name' => '鳳山店', 'brand' => 'BMW', 'model' => '3 Series', 'plate_number' => 'BCD-890', 'year' => 2024],
        ];

        $vehicles = [];
        foreach ($vehicleTemplates as $template) {
            if (!isset($branches[$template['branch_name']])) {
                continue;
            }

            $vehicle = Vehicle::updateOrCreate(
                ['tenant_id' => $tenant->id, 'plate_number' => $template['plate_number']],
                [
                    'current_branch_id' => $branches[$template['branch_name']]->id,
                    'name' => $template['brand'] . ' ' . $template['model'],
                    'manufacture_year' => $template['year'],
                    'status' => \App\Domain\Rental\Enums\VehicleStatus::AVAILABLE,
                    'seats' => 5,
                    'type' => \App\Domain\Rental\Enums\VehicleType::CAR,
                    'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                    'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                ]
            );
            $vehicles[] = $vehicle;
        }

        // 確保車輛都有價格資料
        foreach ($vehicles as $vehicle) {
            if (!$vehicle->pricing) {
                $basePrice = 2000 + rand(500, 3000);
                $vehicle->pricing()->create([
                    'tenant_id' => $tenant->id,
                    'weekday_price' => $basePrice,
                    'weekend_price' => $basePrice * 1.3,
                    'holiday_price' => $basePrice * 1.6,
                ]);
            }
        }

        return $vehicles;
    }



    /**
     * 建立各種預約情境
     */
    private function createReservationsForTenant($tenant, $branches, $vehicles, $customers): void
    {
        $today = Carbon::today();
        $availableVehicles = collect($vehicles)->where('status', Vehicle::STATUS_AVAILABLE);

        if ($availableVehicles->isEmpty() || $customers->isEmpty()) {
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
            $this->command->error("  建立預約失敗: 租戶ID {$tenant->id}, 取車分店ID {$pickupBranchId}, 還車分店ID {$returnBranchId}, 錯誤: " . $e->getMessage());
            // 不丟出例外，讓seeder繼續執行
        }
    }
}
