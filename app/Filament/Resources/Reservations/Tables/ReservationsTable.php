<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Models\Reservation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
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
            Action::make('confirm')
                ->label('確認預約')
                ->color('info')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn(Reservation $record) => $record->status === Reservation::STATUS_PENDING)
                ->action(function (Reservation $record) {
                    try {
                        $record->confirm();
                        \Filament\Notifications\Notification::make()
                            ->title('預約已確認')
                            ->success()
                            ->send();
                    } catch (\RuntimeException $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('操作失敗')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('cancel')
                ->label('取消預約')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn(Reservation $record) => in_array($record->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED]))
                ->action(function (Reservation $record) {
                    try {
                        $record->cancel();
                        \Filament\Notifications\Notification::make()
                            ->title('預約已取消')
                            ->success()
                            ->send();
                    } catch (\RuntimeException $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('操作失敗')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('pickUp')
                ->label('辦理取車')
                ->color('primary')
                ->icon('heroicon-o-truck')
                ->requiresConfirmation()
                ->visible(fn(Reservation $record) => $record->status === Reservation::STATUS_CONFIRMED)
                ->action(function (Reservation $record) {
                    try {
                        $record->pickUp();

                        // 如果還沒有建立訂單，自動建立訂單
                        if (!$record->order()->exists()) {
                            \App\Models\Order::createFromReservation($record);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('已完成取車手續')
                            ->success()
                            ->send();
                    } catch (\RuntimeException $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('操作失敗')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('return')
                ->label('辦理歸還')
                ->color('success')
                ->icon('heroicon-o-archive-box')
                ->requiresConfirmation()
                ->visible(fn(Reservation $record) => $record->status === Reservation::STATUS_PICKED_UP)
                ->action(function (Reservation $record) {
                    try {
                        $record->return();

                        // 如果有關聯訂單，自動完成訂單
                        if ($record->order) {
                            $record->order->complete();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('已完成歸還手續')
                            ->success()
                            ->send();
                    } catch (\RuntimeException $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('操作失敗')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
