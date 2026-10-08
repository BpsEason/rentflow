<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;

class PendingActionsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenant = filament()->getTenant();
        $tenantId = $tenant->id;
        $today = Carbon::today();
        $now = Carbon::now();

        return $table
            ->query(
                Reservation::with(['customer', 'vehicle'])
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) use ($today, $now) {
                        $query->where('status', Reservation::STATUS_PENDING) // 待確認
                            ->orWhere(function ($q) use ($today) {
                                $q->whereDate('start_at', $today) // 今日取車
                                    ->where('status', '!=', Reservation::STATUS_PICKED_UP);
                            })
                            ->orWhere(function ($q) use ($today) {
                                $q->whereDate('end_at', $today) // 今日還車
                                    ->where('status', '!=', Reservation::STATUS_RETURNED);
                            })
                            ->orWhere(function ($q) use ($now) {
                                $q->where('status', Reservation::STATUS_PICKED_UP) // 逾期未還
                                    ->where('end_at', '<', $now);
                            });
                    })
                    ->orderByRaw("CASE 
                        WHEN status = '" . Reservation::STATUS_PENDING . "' THEN 1
                        WHEN end_at < NOW() THEN 2
                        WHEN DATE(start_at) = CURDATE() THEN 3
                        WHEN DATE(end_at) = CURDATE() THEN 4
                        ELSE 5
                    END")
                    ->limit(15)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('預約編號')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('客戶')
                    ->searchable(),
                TextColumn::make('vehicle.name')
                    ->label('車輛')
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('取車時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('還車時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Reservation::STATUS_PENDING => 'warning',
                        Reservation::STATUS_CONFIRMED => 'info',
                        Reservation::STATUS_PICKED_UP => 'primary',
                        Reservation::STATUS_RETURNED => 'success',
                        Reservation::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        Reservation::STATUS_PENDING => '待確認',
                        Reservation::STATUS_CONFIRMED => '已確認',
                        Reservation::STATUS_PICKED_UP => '取車中',
                        Reservation::STATUS_RETURNED => '已歸還',
                        Reservation::STATUS_CANCELLED => '已取消',
                        default => $state,
                    }),
                TextColumn::make('action_type')
                    ->label('需處理')
                    ->badge()
                    ->color(fn($record) => $this->getActionTypeColor($record))
                    ->formatStateUsing(fn($record) => $this->getActionTypeName($record)),
            ])
            ->actions([
                Action::make('view')
                    ->label('查看')
                    ->url(fn(Reservation $record): string => ReservationResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    protected function getActionTypeName($record): string
    {
        $today = Carbon::today();
        $now = Carbon::now();

        if ($record->status === Reservation::STATUS_PENDING) {
            return '待確認';
        }

        if ($record->status === Reservation::STATUS_PICKED_UP && $record->end_at < $now) {
            return '逾期未還';
        }

        if ($record->start_at->isToday() && $record->status !== Reservation::STATUS_PICKED_UP) {
            return '今日取車';
        }

        if ($record->end_at->isToday() && $record->status !== Reservation::STATUS_RETURNED) {
            return '今日還車';
        }

        return '追蹤';
    }

    protected function getActionTypeColor($record): string
    {
        $today = Carbon::today();
        $now = Carbon::now();

        if ($record->status === Reservation::STATUS_PENDING) {
            return 'warning';
        }

        if ($record->status === Reservation::STATUS_PICKED_UP && $record->end_at < $now) {
            return 'danger';
        }

        if ($record->start_at->isToday() && $record->status !== Reservation::STATUS_PICKED_UP) {
            return 'info';
        }

        if ($record->end_at->isToday() && $record->status !== Reservation::STATUS_RETURNED) {
            return 'success';
        }

        return 'gray';
    }
}
