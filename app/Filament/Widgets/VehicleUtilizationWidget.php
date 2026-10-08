<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use App\Models\Vehicle;
use Filament\Widgets\LineChartWidget;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

class VehicleUtilizationWidget extends LineChartWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = '1/2';

    protected function getData(): array
    {
        $tenant = filament()->getTenant();
        $tenantId = $tenant->id;

        // 取得未來 7 天的日期範圍
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(6);

        // 計算可用車輛總數（狀態為 AVAILABLE 的車輛）
        $availableVehiclesQuery = Vehicle::where('tenant_id', $tenantId);
        $totalAvailableVehicles = (clone $availableVehiclesQuery)
            ->where('status', Vehicle::STATUS_AVAILABLE)
            ->count();

        // 建立日期範圍內的所有日期，預設利用率為 0
        $dates = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates->put($date->format('Y-m-d'), [
                'date' => $date->format('m/d'),
                'utilization' => 0,
            ]);
        }

        // 如果沒有可用車輛，直接返回全0數據
        if ($totalAvailableVehicles === 0) {
            return [
                'labels' => $dates->pluck('date')->toArray(),
                'datasets' => [
                    [
                        'label' => '車輛出租率 (%)',
                        'data' => $dates->pluck('utilization')->toArray(),
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                ],
            ];
        }

        // 查詢未來7天每天被占用的車輛數量
        // 邏輯：預約的時間區間包含當天，且狀態為 BLOCKING_STATUSES（有效預約）
        $reservationQuery = Reservation::where('tenant_id', $tenantId);
        $utilizationData = collect();

        // 遍歷每一天計算利用率
        foreach ($dates as $dateStr => $dateData) {
            $currentDate = Carbon::parse($dateStr);

            // 查詢當天被占用的獨特車輛數量
            // 預約的時間區間與當天有重疊：start_at <= 當天結束 AND end_at > 當天開始
            $rentedVehiclesCount = (clone $reservationQuery)
                ->whereIn('status', Reservation::BLOCKING_STATUSES)
                ->where('start_at', '<=', $currentDate->copy()->endOfDay())
                ->where('end_at', '>', $currentDate->copy()->startOfDay())
                ->distinct('vehicle_id')
                ->count('vehicle_id');

            // 計算利用率百分比
            $utilization = round(($rentedVehiclesCount / $totalAvailableVehicles) * 100, 1);

            $dates->put($dateStr, array_merge($dateData, [
                'utilization' => $utilization,
            ]));
        }

        return [
            'labels' => $dates->pluck('date')->toArray(),
            'datasets' => [
                [
                    'label' => '車輛出租率 (%)',
                    'data' => $dates->pluck('utilization')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    public function getHeading(): string
    {
        return '未來7天車隊利用率';
    }
}
