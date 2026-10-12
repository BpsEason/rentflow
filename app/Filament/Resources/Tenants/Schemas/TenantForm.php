<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('租戶基本資料')
                    ->description('管理租戶的基本識別資訊，系統將使用識別碼作為路由前綴')
                    ->icon('heroicon-o-building-office')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('租戶名稱')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('輸入租戶名稱'),

                        TextInput::make('slug')
                            ->label('系統識別碼')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('此識別碼將用於系統URL路由，請勿隨意修改')
                            ->placeholder('輸入系統識別碼'),

                        \Filament\Forms\Components\Toggle::make('is_public')
                            ->label('允許公開租車服務')
                            ->helperText('開啟後此租戶的車輛將可在前台公開展示並接受訪客預約')
                            ->default(false),
                    ]),
            ]);
    }
}
