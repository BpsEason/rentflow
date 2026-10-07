<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Models\Reservation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ReservationsTable
{
    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable(),

            TextColumn::make('customer.name')
                ->label('客戶')
                ->sortable()
                ->searchable(),

            TextColumn::make('vehicle.name')
                ->label('車輛')
                ->sortable()
                ->searchable(),

            TextColumn::make('start_at')
                ->label('開始時間')
                ->dateTime('Y-m-d H:i')
                ->sortable(),

            TextColumn::make('end_at')
                ->label('結束時間')
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
                    Reservation::STATUS_PICKED_UP => '已取車',
                    Reservation::STATUS_RETURNED => '已歸還',
                    Reservation::STATUS_CANCELLED => '已取消',
                    default => $state,
                }),

            TextColumn::make('rental_days')
                ->label('租用天數')
                ->sortable(),

            TextColumn::make('amount')
                ->label('總金額')
                ->money('TWD')
                ->sortable(),

            TextColumn::make('created_at')
                ->label('建立時間')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function getFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('狀態')
                ->options([
                    Reservation::STATUS_PENDING => '待確認',
                    Reservation::STATUS_CONFIRMED => '已確認',
                    Reservation::STATUS_PICKED_UP => '已取車',
                    Reservation::STATUS_RETURNED => '已歸還',
                    Reservation::STATUS_CANCELLED => '已取消',
                ]),
        ];
    }

    public static function getActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
