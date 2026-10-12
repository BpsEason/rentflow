<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;

class CustomerForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('客戶基本資料')
                    ->description('管理客戶的基本聯絡資訊')
                    ->icon('heroicon-o-user')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('客戶姓名')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('輸入客戶姓名'),

                        TextInput::make('email')
                            ->label('電子郵件')
                            ->email()
                            ->nullable()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                $tenant = filament()->getTenant();
                                if ($tenant) {
                                    return $rule->where('tenant_id', $tenant->id);
                                }
                                return $rule;
                            })
                            ->placeholder('example@email.com'),

                        TextInput::make('phone')
                            ->label('聯絡電話')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                $tenant = filament()->getTenant();
                                if ($tenant) {
                                    return $rule->where('tenant_id', $tenant->id);
                                }
                                return $rule;
                            })
                            ->placeholder('輸入聯絡電話'),
                    ]),

                Section::make('帳戶狀態')
                    ->description('設定客戶的帳戶狀態')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        Toggle::make('is_active')
                            ->label('啟用狀態')
                            ->default(true)
                            ->helperText('關閉後客戶將無法建立新的預約'),
                    ]),

                Section::make('身分與駕駛資料')
                    ->description('選填的身分及駕照資料')
                    ->icon('heroicon-o-identification')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        TextInput::make('id_number')
                            ->label('身分證字號')
                            ->maxLength(255)
                            ->autocomplete(false),

                        TextInput::make('license_number')
                            ->label('駕照號碼')
                            ->maxLength(255)
                            ->autocomplete(false),

                        DatePicker::make('license_expiry')
                            ->label('駕照有效日期')
                            ->native(false),
                    ]),

                Section::make('地址與緊急聯絡人')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        Textarea::make('address')
                            ->label('聯絡地址')
                            ->columnSpanFull(),

                        TextInput::make('emergency_contact_name')
                            ->label('緊急聯絡人姓名')
                            ->maxLength(255),

                        TextInput::make('emergency_contact_phone')
                            ->label('緊急聯絡人電話')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
