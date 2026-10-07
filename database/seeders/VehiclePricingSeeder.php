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
            // 小型車
            'Toyota Vios' => [
                'weekday' => 1300,
                'weekend' => 1600,
                'holiday' => 2000,
            ],
            'Honda Fit' => [
                'weekday' => 1400,
                'weekend' => 1700,
                'holiday' => 2100,
            ],

            // 中型房車
            'Toyota Altis' => [
                'weekday' => 1800,
                'weekend' => 2200,
                'holiday' => 2700,
            ],

            // 國產休旅
            'Luxgen U6' => [
                'weekday' => 2000,
                'weekend' => 2500,
                'holiday' => 3000,
            ],

            // 七人座
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

            // 中型休旅（較高價）
            'Honda CR-V' => [
                'weekday' => 2800,
                'weekend' => 3500,
                'holiday' => 4200,
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
