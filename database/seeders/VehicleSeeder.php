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
        // 更貼近台灣租車行實際會有的車款
        $vehiclesTemplate = [
            // available（可出租）
            [
                'name'        => 'Toyota Altis',
                'seats'       => 5,
                'status'      => \App\Models\Vehicle::STATUS_AVAILABLE,
                'description' => '最常見的國民房車，油耗表現穩定，適合長途與日常使用',
            ],
            [
                'name'        => 'Toyota Vios',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '小巧好開、停車方便，都會區短租首選',
            ],
            [
                'name'        => 'Honda Fit',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '後座空間靈活，適合小家庭或帶行李出遊',
            ],
            [
                'name'        => 'Toyota Sienta',
                'seats'       => 7,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '七人座滑門設計，家庭出遊與機場接送很受歡迎',
            ],
            [
                'name'        => 'Luxgen U6',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_AVAILABLE,
                'description' => '國產休旅，空間充足，性價比高',
            ],

            // maintenance（保養中）
            [
                'name'        => 'Honda CR-V',
                'seats'       => 5,
                'status'      => Vehicle::STATUS_MAINTENANCE,
                'description' => '中型休旅，目前進行定期保養，暫時無法出租',
            ],

            // inactive（停用）
            [
                'name'        => 'Mitsubishi Zinger',
                'seats'       => 7,
                'status'      => Vehicle::STATUS_INACTIVE,
                'description' => '舊款七人座，已暫停營運，等待後續處理',
            ],
        ];

        // 台灣常見車牌英文前綴（依地區區分，方便識別）
        $platePrefixes = [
            1 => 'RTA', // 台北租車
            2 => 'RTC', // 台中租車
            3 => 'RTK', // 高雄租車
        ];

        // 較自然的數字組合
        $plateNumbers = ['1234', '5678', '9012', '3456', '7890', '2345', '6789'];

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
