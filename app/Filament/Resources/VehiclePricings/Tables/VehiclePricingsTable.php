<?php

namespace App\Filament\Resources\VehiclePricings\Tables;

use App\Domain\Rental\Models\Vehicle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiclePricingsTable
{
    public static function configure(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('vehicle.name')
                    ->label('車輛名稱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('weekday_price')
                    ->label('平日單價')
                    ->money('TWD')
                    ->sortable(),

                TextColumn::make('weekend_price')
                    ->label('週末單價')
                    ->money('TWD')
                    ->sortable(),

                TextColumn::make('holiday_price')
                    ->label('國定假日單價')
                    ->money('TWD')
                    ->sortable(),

                // 只有超級管理員能看到租戶資訊
                TextColumn::make('tenant.name')
                    ->label('所屬租戶')
                    ->visible($user->hasRole('Super Admin'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
