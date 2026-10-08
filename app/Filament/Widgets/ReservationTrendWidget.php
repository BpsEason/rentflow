<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Filament\Widgets\LineChartWidget;
use Carbon\Carbon;
use Filament\Facades\Filament;

class ReservationTrendWidget extends LineChartWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = '1/2';

    protected function getData(): array
    {
        $tenant = filament()->getTenant();
        $tenantId = $tenant->id;

        // 取得未來 7 天的日期範圍（今天 + 往後6天）
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(6);

        // 建立日期範圍內的所有日期，預設值為 0
        $dates = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates->put($date->format('Y-m-d'), [
                'date' => $date->format('m/d'),
                'total' => 0,
            ]);
        }

        // 查詢未來7天內每天的取車預約數量（有效狀態的預約）
        $query = Reservation::where('tenant_id', $tenantId);
        $reservations = (clone $query)
            ->whereIn('status', Reservation::BLOCKING_STATUSES) // 只統計有效狀態的預約
            ->whereDate('start_at', '>=', $startDate)
            ->whereDate('start_at', '<=', $endDate)
            ->selectRaw('DATE(start_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        // 將實際數據填入日期陣列
        foreach ($reservations as $reservation) {
            if ($dates->has($reservation->date)) {
                $current = $dates->get($reservation->date);
                $current['total'] = $reservation->count;
                $dates->put($reservation->date, $current);
            }
        }

        return [
            'labels' => $dates->pluck('date')->toArray(),
            'datasets' => [
                [
                    'label' => '每日取車預約數',
                    'data' => $dates->pluck('total')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    public function getHeading(): string
    {
        return '未來7天預約趨勢';
    }
}
