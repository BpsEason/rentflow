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
            ],
            [
                'name'        => 'Honda CR-V',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_MAINTENANCE,
                'description' => '中型休旅，進行定期保養，暫時無法出租',
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
        ];

        // 台灣常見車牌英文前綴（依地區區分，方便識別）
        $platePrefixes = [
            1 => 'RTA', // 台北租車
            2 => 'RTC', // 台中租車
            3 => 'RTK', // 高雄租車
        ];

        // 較自然的數字組合 - 擴充到15組號碼
        $plateNumbers = ['1001', '1002', '1003', '1004', '1005', '1006', '1007', '1008', '1009', '1010', '1011', '1012', '1013', '1014', '1015'];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $prefix = $platePrefixes[$tenant->id] ?? 'RTX';

            foreach ($vehiclesTemplate as $index => $vehicleData) {
                $plateNumber = $prefix . '-' . $plateNumbers[$index];

                Vehicle::updateOrCreate(
                    [
                        'tenant_id'    => $tenant->id,
                        'plate_number' => $plateNumber,
                    ],
                    [
                        'name'        => $vehicleData['name'],
                        'status'      => $vehicleData['status'],
                        'seats'       => $vehicleData['seats'],
                        'description' => $vehicleData['description'],
                    ]
                );
            }
        }
    }
}
