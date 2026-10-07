<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('基本帳號資訊')
                    ->description('管理使用者的基本帳號與登入資訊')
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
                            ->label('名稱')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('輸入使用者名稱'),

                        TextInput::make('email')
                            ->label('電子郵件')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('example@company.com'),

                        TextInput::make('password')
                            ->label('密碼')
                            ->password()
                            ->dehydrated(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->required(fn(string $context) => $context === 'create')
                            ->minLength(8)
                            ->helperText('建立使用者時必填，編輯使用者時留空代表維持原密碼')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ]),

                Section::make('存取權限')
                    ->description('設定使用者可以使用的角色與租戶範圍')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        Select::make('roles')
                            ->label('角色')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->placeholder('選擇角色'),

                        Select::make('tenants')
                            ->label('租戶')
                            ->relationship('tenants', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->placeholder('選擇租戶'),
                    ]),
            ]);
    }
}
