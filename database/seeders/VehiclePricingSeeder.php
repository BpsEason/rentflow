<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Domain\Rental\Models\Vehicle;
use App\Domain\Rental\Models\VehiclePricing;
use Illuminate\Database\Seeder;

class VehiclePricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * 台灣中小型租車行常見日租行情（單位：新台幣）
         * 參考基準：
         * - 平日較低、週末上漲、連續假期再往上加
         * - 小車便宜、七人座與休旅明顯較高
         */
        $vehicleBasePrices = [
            // 經濟型 - 小型車
            'Toyota Yaris' => [
                'weekday' => 1200,
                'weekend' => 1500,
                'holiday' => 1900,
            ],
            'Honda Fit' => [
                'weekday' => 1400,
                'weekend' => 1700,
                'holiday' => 2100,
            ],
            'Mazda 3' => [
                'weekday' => 1500,
                'weekend' => 1800,
                'holiday' => 2200,
            ],

            // 中型房車
            'Toyota Corolla Altis' => [
                'weekday' => 1800,
                'weekend' => 2200,
                'holiday' => 2700,
            ],

            // SUV/休旅車
            'Toyota Corolla Cross' => [
                'weekday' => 2000,
                'weekend' => 2500,
                'holiday' => 3100,
            ],
            'Toyota RAV4' => [
                'weekday' => 2400,
                'weekend' => 3000,
                'holiday' => 3700,
            ],
            'Honda HR-V' => [
                'weekday' => 2200,
                'weekend' => 2700,
                'holiday' => 3300,
            ],
            'Nissan Kicks' => [
                'weekday' => 2100,
                'weekend' => 2600,
                'holiday' => 3200,
            ],
            'Mazda CX-5' => [
                'weekday' => 2500,
                'weekend' => 3100,
                'holiday' => 3800,
            ],
            'Honda CR-V' => [
                'weekday' => 2800,
                'weekend' => 3500,
                'holiday' => 4200,
            ],

            // 七人座/多人座
            'Toyota Sienta' => [
                'weekday' => 2500,
                'weekend' => 3200,
                'holiday' => 3800,
            ],
            'Mitsubishi Zinger' => [
                'weekday' => 2200,
                'weekend' => 2800,
                'holiday' => 3400,
            ],
            'Toyota Innova' => [
                'weekday' => 2000,
                'weekend' => 2500,
                'holiday' => 3000,
            ],
        ];

        $tenants = Tenant::with('vehicles')->get();

        foreach ($tenants as $tenant) {
            foreach ($tenant->vehicles as $vehicle) {
                $prices = $vehicleBasePrices[$vehicle->name] ?? [
                    'weekday' => 1800,
                    'weekend' => 2200,
                    'holiday' => 2700,
                ];

                VehiclePricing::updateOrCreate(
                    [
                        'tenant_id'  => $tenant->id,
                        'vehicle_id' => $vehicle->id,
                    ],
                    [
                        'weekday_price' => $prices['weekday'],
                        'weekend_price' => $prices['weekend'],
                        'holiday_price' => $prices['holiday'],
                    ]
                );
            }
        }
    }
}
