<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('order_number')
                ->label('訂單編號')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('reservation.id')
                ->label('預約ID')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('reservation.customer.name')
                ->label('客戶')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('reservation.vehicle.name')
                ->label('車輛')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('total_amount')
                ->label('總金額')
                ->money('TWD')
                ->sortable(),

            Tables\Columns\SelectColumn::make('status')
                ->label('狀態')
                ->options([
                    Order::STATUS_PENDING => '待處理',
                    Order::STATUS_CONFIRMED => '已確認',
                    Order::STATUS_CANCELLED => '已取消',
                    Order::STATUS_COMPLETED => '已完成',
                ])
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('建立時間')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
        ];
    }

    public static function getFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->label('狀態')
                ->options([
                    Order::STATUS_PENDING => '待處理',
                    Order::STATUS_CONFIRMED => '已確認',
                    Order::STATUS_CANCELLED => '已取消',
                    Order::STATUS_COMPLETED => '已完成',
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
