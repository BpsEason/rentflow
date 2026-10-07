<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Domain\Rental\Enums\VehicleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('車輛名稱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plate_number')
                    ->label('車牌號碼')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('車輛狀態')
                    ->badge()
                    ->color(fn(VehicleStatus $state): string => match ($state) {
                        VehicleStatus::AVAILABLE => 'success',
                        VehicleStatus::MAINTENANCE => 'warning',
                        VehicleStatus::INACTIVE => 'danger',
                    })
                    ->formatStateUsing(fn(VehicleStatus $state): string => match ($state) {
                        VehicleStatus::AVAILABLE => '可用',
                        VehicleStatus::MAINTENANCE => '維修中',
                        VehicleStatus::INACTIVE => '停用',
                    }),

                TextColumn::make('seats')
                    ->label('座位數')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('車輛狀態')
                    ->options([
                        VehicleStatus::AVAILABLE->value => '可用',
                        VehicleStatus::MAINTENANCE->value => '維修中',
                        VehicleStatus::INACTIVE->value => '停用',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
