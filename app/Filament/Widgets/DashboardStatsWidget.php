<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Filament\Facades\Filament;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenant = filament()->getTenant();
        $tenantId = $tenant->id;
        $today = Carbon::today();
        $now = Carbon::now();

        // 基礎查詢條件，確保 Tenant Isolation
        $reservationQuery = Reservation::where('tenant_id', $tenantId);
        $orderQuery = Order::where('tenant_id', $tenantId);

        // 逾期未還車：已取車但 end_at < 現在
        $overdueCount = (clone $reservationQuery)
            ->where('status', Reservation::STATUS_PICKED_UP)
            ->where('end_at', '<', $now)
            ->count();

        // 今日訂單金額
        $todayOrderAmount = (clone $orderQuery)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        return [
            // 待確認預約
            Stat::make('待確認預約', (clone $reservationQuery)->where('status', Reservation::STATUS_PENDING)->count())
                ->description('等待確認的預約')
                ->color('warning')
                ->url(route('filament.admin.resources.reservations.index', ['tenant' => $tenant, 'tableFilters[status][value]' => Reservation::STATUS_PENDING])),

            // 今日取車
            Stat::make('今日取車', (clone $reservationQuery)->whereDate('start_at', $today)->count())
                ->description('今日需取車的預約')
                ->color('info')
                ->url(route('filament.admin.resources.reservations.index', ['tenant' => $tenant, 'tableFilters[start_at][value]' => $today->format('Y-m-d')])),

            // 今日還車
            Stat::make('今日還車', (clone $reservationQuery)->whereDate('end_at', $today)->count())
                ->description('今日需歸還的預約')
                ->color('success')
                ->url(route('filament.admin.resources.reservations.index', ['tenant' => $tenant, 'tableFilters[end_at][value]' => $today->format('Y-m-d')])),

            // 逾期未還車
            Stat::make('逾期未還車', $overdueCount)
                ->description('已過歸還時間未還車')
                ->color('danger')
                ->url(route('filament.admin.resources.reservations.index', ['tenant' => $tenant, 'tableFilters[status][value]' => Reservation::STATUS_PICKED_UP])),

            // 今日訂單
            Stat::make('今日訂單', (clone $orderQuery)->whereDate('created_at', $today)->count())
                ->description('今日新增的訂單數')
                ->color('primary'),

            // 今日訂單金額
            Stat::make('今日訂單金額', '$' . number_format($todayOrderAmount, 0))
                ->description('今日累計訂單金額 (TWD)')
                ->color('success'),
        ];
    }
}
