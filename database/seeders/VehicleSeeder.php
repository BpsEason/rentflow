<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 車型與圖片的對應表 - 每個車型對應一張固定的圖片
        $vehicleImageMap = [
            'Toyota Yaris' => 'vehicles/toyota-yaris.jpg',
            'Toyota Corolla Altis' => 'vehicles/toyota-corolla-altis.jpg',
            'Toyota Corolla Cross' => 'vehicles/toyota-corolla-cross.jpg',
            'Toyota RAV4' => 'vehicles/toyota-rav4.jpg',
            'Toyota Sienta' => 'vehicles/toyota-sienta.jpg',
            'Toyota Innova' => 'vehicles/toyota-innova.jpg',
            'Honda Fit' => 'vehicles/honda-fit.jpg',
            'Honda HR-V' => 'vehicles/honda-hrv.jpg',
            'Honda CR-V' => 'vehicles/honda-crv.jpg',
            'Nissan Kicks' => 'vehicles/nissan-kicks.jpg',
            'Mazda 3' => 'vehicles/mazda-3.jpg',
            'Mazda CX-5' => 'vehicles/mazda-cx5.jpg',
            'Mitsubishi Zinger' => 'vehicles/mitsubishi-zinger.jpg',
            // 機車車款
            'Yamaha Jog 115' => 'vehicles/yamaha-jog.jpg',
            'Kymco Racing 125' => 'vehicles/kymco-racing.jpg',
            'Yamaha NMAX 155' => 'vehicles/yamaha-nmax.jpg',
            'SYM Jet SL 158' => 'vehicles/sym-jet.jpg',
            'Kymco AK550' => 'vehicles/kymco-ak550.jpg',
        ];

        // 常見車款的預設屬性
        $vehicleDefaults = [
            'Toyota Yaris' => [
                'manufacture_year' => 2021,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 80000,
            ],
            'Toyota Corolla Altis' => [
                'manufacture_year' => 2022,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 60000,
            ],
            'Toyota Corolla Cross' => [
                'manufacture_year' => 2023,
                'fuel_type' => \App\Models\Vehicle::FUEL_HYBRID,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 35000,
            ],
            'Toyota RAV4' => [
                'manufacture_year' => 2023,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 40000,
            ],
            'Toyota Sienta' => [
                'manufacture_year' => 2022,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 55000,
            ],
            'Toyota Innova' => [
                'manufacture_year' => 2018,
                'fuel_type' => \App\Models\Vehicle::FUEL_DIESEL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 180000,
            ],
            'Honda Fit' => [
                'manufacture_year' => 2021,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 75000,
            ],
            'Honda HR-V' => [
                'manufacture_year' => 2022,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 50000,
            ],
            'Honda CR-V' => [
                'manufacture_year' => 2021,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 70000,
            ],
            'Nissan Kicks' => [
                'manufacture_year' => 2022,
                'fuel_type' => \App\Models\Vehicle::FUEL_ELECTRIC,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 45000,
            ],
            'Mazda 3' => [
                'manufacture_year' => 2021,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 65000,
            ],
            'Mazda CX-5' => [
                'manufacture_year' => 2020,
                'fuel_type' => \App\Models\Vehicle::FUEL_DIESEL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 95000,
            ],
            'Mitsubishi Zinger' => [
                'manufacture_year' => 2017,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 210000,
            ],
            // 機車車款預設屬性
            'Yamaha Jog 115' => [
                'manufacture_year' => 2022,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 25000,
                'engine_cc' => 115,
                'helmet_count' => 2,
            ],
            'Kymco Racing 125' => [
                'manufacture_year' => 2021,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 35000,
                'engine_cc' => 125,
                'helmet_count' => 2,
            ],
            'Yamaha NMAX 155' => [
                'manufacture_year' => 2023,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 15000,
                'engine_cc' => 155,
                'helmet_count' => 2,
            ],
            'SYM Jet SL 158' => [
                'manufacture_year' => 2022,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 20000,
                'engine_cc' => 158,
                'helmet_count' => 2,
            ],
            'Kymco AK550' => [
                'manufacture_year' => 2021,
                'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                'base_mileage' => 18000,
                'engine_cc' => 550,
                'helmet_count' => 2,
            ],
        ];

        // 更貼近台灣租車行實際會有的車款 - 總共15台車，符合70%可用、15%保養中、15%停用的比例
        $vehiclesTemplate = [
            // available（可出租）- 11台 (73%)
            [
                'name'        => 'Toyota Yaris',
                'seats'       => 5,
                'status'      => \App\Models\Vehicle::STATUS_AVAILABLE,
                'description' => '小巧都會代步車，市區好開停車方便',
            ],
            [
                'name'        => 'Toyota Yaris',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '第二台Yaris，高使用率車型',
            ],
            [
                'name'        => 'Toyota Corolla Altis',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '國民房車，油耗表現穩定，適合長途與日常使用',
            ],
            [
                'name'        => 'Toyota Corolla Altis',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '第二台Altis，商務出差首選',
            ],
            [
                'name'        => 'Toyota Corolla Cross',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '跨界休旅，空間實用適合家庭出遊',
            ],
            [
                'name'        => 'Toyota RAV4',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '熱門中型休旅，越野與舒適兼備',
            ],
            [
                'name'        => 'Honda Fit',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '後座空間靈活，適合小家庭或帶行李出遊',
            ],
            [
                'name'        => 'Honda HR-V',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '日系都會休旅，設計時尚吸引年輕族群',
            ],
            [
                'name'        => 'Nissan Kicks',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '省油都會休旅，適合日常通勤與輕旅行',
            ],
            [
                'name'        => 'Mazda 3',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '駕駛樂趣優質，操控靈活的進口房車',
            ],
            [
                'name'        => 'Toyota Sienta',
                'seats'       => 7,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '七人座滑門設計，家庭出遊與機場接送很受歡迎',
            ],

            // maintenance（保養中）- 2台 (13%)
            [
                'name'        => 'Mazda CX-5',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_MAINTENANCE,
                'description' => '進口中型休旅，目前進行定期保養中',
                'maintenance_start_at' => now(),
                'maintenance_expected_end_at' => now()->addDays(3),
                'maintenance_notes' => '進行定期保養，更換機油與煞車檢查',
            ],
            [
                'name'        => 'Honda CR-V',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_MAINTENANCE,
                'description' => '中型休旅，進行定期保養，暫時無法出租',
                'maintenance_start_at' => now()->subDay(),
                'maintenance_expected_end_at' => now()->addDays(2),
                'maintenance_notes' => '底盤維修與輪胎更換',
            ],

            // inactive（停用）- 2台 (13%)
            [
                'name'        => 'Mitsubishi Zinger',
                'seats'       => 7,
                'status'      => Vehicle::STATUS_INACTIVE,
                'description' => '舊款七人座，已暫停營運，等待後續處理',
            ],
            [
                'name'        => 'Toyota Innova',
                'seats'       => 8,
                'status'      => Vehicle::STATUS_INACTIVE,
                'description' => '老款八人座，車齡已高暫停營運',
            ],

            // 機車車輛模板 - 加入5台機車，符合展示需求
            [
                'name'        => 'Yamaha Jog 115',
                'seats'       => 1,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'type'        => \App\Domain\Rental\Enums\VehicleType::MOTORCYCLE,
                'description' => '經典都會通勤車，省油好停車，適合短程移動',
            ],
            [
                'name'        => 'Kymco Racing 125',
                'seats'       => 2,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'type'        => \App\Domain\Rental\Enums\VehicleType::MOTORCYCLE,
                'description' => '熱門運動速克達，動力充足適合雙載',
            ],
            [
                'name'        => 'Yamaha NMAX 155',
                'seats'       => 2,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'type'        => \App\Domain\Rental\Enums\VehicleType::MOTORCYCLE,
                'description' => '黃牌大型重型機車，舒適穩定適合長途旅行',
            ],
            [
                'name'        => 'SYM Jet SL 158',
                'seats'       => 2,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'type'        => \App\Domain\Rental\Enums\VehicleType::MOTORCYCLE,
                'description' => '時尚外型黃牌機車，都會通勤首選',
            ],
            [
                'name'        => 'Kymco AK550',
                'seats'       => 2,
                'status'      => Vehicle::STATUS_MAINTENANCE,
                'type'        => \App\Domain\Rental\Enums\VehicleType::MOTORCYCLE,
                'description' => '頂級大羊重機，目前進行定期保養中',
                'maintenance_start_at' => now(),
                'maintenance_expected_end_at' => now()->addDays(2),
                'maintenance_notes' => '進行定期保養，更換機油與煞車檢查',
            ],
        ];

        // 台灣常見車牌英文前綴（依地區區分，方便識別）
        $platePrefixes = [
            1 => ['ABC', 'DEF', 'GHI', 'JKL', 'MNO'], // 台北租車 - 大台北地區常用前綴
            2 => ['PQR', 'STU', 'VWX', 'YZA', 'BCD'], // 台中租車 - 中台灣常用前綴
            3 => ['EFG', 'HIJ', 'KLM', 'NOP', 'QRS'], // 高雄租車 - 南台灣常用前綴
        ];

        // 針對不同規模的租戶，給予不同數量的車輛 (包含新加入的5台機車，第16-20台為機車)
        $tenantVehicleCounts = [
            1 => 20, // 台北租車 - 規模最大，取全部20台（15台汽車+5台機車）
            2 => 20, // 台中租車 - 中型規模，同樣取全部20台，確保能拿到5台機車
            3 => 20, // 高雄租車 - 小型規模，同樣取全部20台，確保能拿到5台機車
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $prefixes = $platePrefixes[$tenant->id] ?? ['XYZ'];
            $vehicleCount = $tenantVehicleCounts[$tenant->id] ?? 5;

            // 取得此租戶的所有活躍據點，用於分配車輛
            $tenantBranches = \App\Models\Branch::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            // 如果沒有據點，先建立預設據點
            if ($tenantBranches->isEmpty()) {
                $defaultBranch = \App\Models\Branch::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => '預設分店'],
                    [
                        'address' => '預設地址',
                        'phone' => '02-12345678',
                        'is_active' => true,
                        'business_hours' => json_encode([
                            'monday' => ['09:00', '21:00'],
                            'tuesday' => ['09:00', '21:00'],
                            'wednesday' => ['09:00', '21:00'],
                            'thursday' => ['09:00', '21:00'],
                            'friday' => ['09:00', '22:00'],
                            'saturday' => ['08:00', '22:00'],
                            'sunday' => ['08:00', '21:00'],
                        ])
                    ]
                );
                $tenantBranches->push($defaultBranch);
            }

            // 從模板中取前N台車，確保不同租戶有不同數量的車輛
            $tenantVehicles = array_slice($vehiclesTemplate, 0, $vehicleCount);

            foreach ($tenantVehicles as $index => $vehicleData) {
                // 生成真實的台灣車牌格式：3英數-4數字，例如ABC-1234
                $prefix = $prefixes[$index % count($prefixes)];
                $number = str_pad((string)(1000 + $index), 4, '0', STR_PAD_LEFT);
                $plateNumber = $prefix . '-' . $number;

                // 取得對應的圖片路徑
                $imagePath = $vehicleImageMap[$vehicleData['name']] ?? null;

                // 從預設值取得車輛基本屬性
                $defaults = $vehicleDefaults[$vehicleData['name']] ?? [
                    'manufacture_year' => 2020,
                    'fuel_type' => \App\Models\Vehicle::FUEL_PETROL,
                    'transmission' => \App\Models\Vehicle::TRANSMISSION_AUTOMATIC,
                    'base_mileage' => 100000,
                ];

                // 輪流分配車輛到各個據點
                $branchIndex = $index % $tenantBranches->count();
                $assignedBranch = $tenantBranches[$branchIndex];

                // 為同一型號的第二台以上車輛增加里程數
                $mileageOffset = $index * 5000;

                $vehicleAttributes = [
                    'current_branch_id' => $assignedBranch->id,
                    'name'        => $vehicleData['name'],
                    'status'      => $vehicleData['status'],
                    'seats'       => $vehicleData['seats'],
                    'description' => $vehicleData['description'],
                    'image'       => $imagePath,
                    'manufacture_year' => $defaults['manufacture_year'],
                    'mileage'     => $defaults['base_mileage'] + $mileageOffset,
                    'fuel_type'   => $defaults['fuel_type'],
                    'transmission' => $defaults['transmission'],
                ];

                // 如果是機車，加入排氣量和安全帽數量
                if (isset($vehicleData['type']) && $vehicleData['type'] === \App\Domain\Rental\Enums\VehicleType::MOTORCYCLE) {
                    $vehicleAttributes['type'] = $vehicleData['type'];
                    $vehicleAttributes['engine_cc'] = $defaults['engine_cc'] ?? null;
                    $vehicleAttributes['helmet_count'] = $defaults['helmet_count'] ?? 2;
                } else {
                    // 預設為汽車類型
                    $vehicleAttributes['type'] = \App\Domain\Rental\Enums\VehicleType::CAR;
                }

                // 如果是維修中的車輛，加入維修時間資訊
                if (isset($vehicleData['maintenance_start_at'])) {
                    $vehicleAttributes['maintenance_start_at'] = $vehicleData['maintenance_start_at'];
                    $vehicleAttributes['maintenance_expected_end_at'] = $vehicleData['maintenance_expected_end_at'];
                    $vehicleAttributes['maintenance_notes'] = $vehicleData['maintenance_notes'];
                }

                Vehicle::updateOrCreate(
                    [
                        'tenant_id'    => $tenant->id,
                        'plate_number' => $plateNumber,
                    ],
                    $vehicleAttributes
                );
            }
        }
    }
}
