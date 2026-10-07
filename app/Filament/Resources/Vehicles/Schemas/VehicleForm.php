<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Domain\Rental\Enums\VehicleStatus;
use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;

class VehicleForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('車輛基本資料')
                    ->description('管理車輛的基本識別資訊與目前可用狀態')
                    ->icon('heroicon-o-truck')
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
                            ->label('車輛名稱')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('輸入車輛名稱'),

                        TextInput::make('plate_number')
                            ->label('車牌號碼')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                $tenant = filament()->getTenant();
                                if ($tenant) {
                                    return $rule->where('tenant_id', $tenant->id);
                                }
                                return $rule;
                            })
                            ->placeholder('輸入車牌號碼'),

                        Textarea::make('description')
                            ->label('車輛描述')
                            ->rows(3)
                            ->nullable()
                            ->placeholder('輸入車輛的詳細描述')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ]),

                Section::make('車輛規格與狀態')
                    ->description('設定車輛的規格參數與營運狀態')
                    ->icon('heroicon-o-cog')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        Select::make('status')
                            ->label('車輛狀態')
                            ->options(VehicleStatus::class)
                            ->required()
                            ->placeholder('選擇車輛狀態'),

                        TextInput::make('seats')
                            ->label('座位數')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->placeholder('輸入座位數'),
                    ]),
            ]);
    }
}
