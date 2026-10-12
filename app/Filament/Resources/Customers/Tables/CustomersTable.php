<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Domain\Rental\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Customer::query())
            ->columns([
                TextColumn::make('name')
                    ->label('客戶姓名')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('電子郵件')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('phone')
                    ->label('聯絡電話')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('id_number')
                    ->label('身分證字號')
                    ->formatStateUsing(fn (?string $state): ?string => $state
                        ? str_repeat('•', max(strlen($state) - 4, 0)) . substr($state, -4)
                        : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('license_number')
                    ->label('駕照號碼')
                    ->formatStateUsing(fn (?string $state): ?string => $state
                        ? str_repeat('•', max(strlen($state) - 4, 0)) . substr($state, -4)
                        : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('license_expiry')
                    ->label('駕照有效日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label('聯絡地址')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('emergency_contact_name')
                    ->label('緊急聯絡人')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('emergency_contact_phone')
                    ->label('緊急聯絡電話')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('啟用狀態')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('啟用狀態')
                    ->placeholder('全部狀態')
                    ->trueLabel('只顯示啟用')
                    ->falseLabel('只顯示停用'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
