<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Domain\Rental\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use App\Services\CustomerMemberPasswordService;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
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

                TextColumn::make('tenant.name')
                    ->label('所屬租戶')
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('會員帳號')
                    ->placeholder('未關聯')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),

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
                Action::make('setMemberPassword')
                    ->label('設定會員新密碼')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('manager_password')
                            ->label('管理者目前密碼')
                            ->password()
                            ->required(),
                        TextInput::make('new_password')
                            ->label('會員新密碼')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        TextInput::make('new_password_confirmation')
                            ->label('再次輸入會員新密碼')
                            ->password()
                            ->required(),
                    ])
                    ->modalHeading('設定會員新密碼')
                    ->modalDescription('請重新驗證管理者密碼，並輸入至少 8 個字元的新密碼。')
                    ->visible(fn (Customer $record): bool => $record->user !== null)
                    ->authorize(fn (Customer $record): bool => Gate::allows('update', $record))
                    ->action(function (Customer $record, array $data): void {
                        app(CustomerMemberPasswordService::class)->set(
                            auth()->user(),
                            $record,
                            $data['manager_password'],
                            $data['new_password'],
                        );

                        Notification::make()
                            ->title('會員密碼已更新')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
