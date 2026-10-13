<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Domain\Rental\Enums\VehicleStatus;
use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use App\Models\Vehicle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;

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
                        'lg' => 6,
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

                        FileUpload::make('image')
                            ->label('車輛圖片')
                            ->image()
                            ->directory('vehicles')
                            ->nullable()
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
                        'lg' => 6,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        Select::make('status')
                            ->label('車輛狀態')
                            ->options([
                                VehicleStatus::AVAILABLE->value => '可用',
                                VehicleStatus::MAINTENANCE->value => '維修中',
                                VehicleStatus::INACTIVE->value => '停用',
                            ])
                            ->required()
                            ->placeholder('選擇車輛狀態'),

                        TextInput::make('seats')
                            ->label('座位數')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->placeholder('輸入座位數'),

                        TextInput::make('manufacture_year')
                            ->label('出廠年份')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(date('Y'))
                            ->nullable()
                            ->placeholder('輸入出廠年份'),

                        TextInput::make('mileage')
                            ->label('目前里程數(公里)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->placeholder('輸入目前里程數'),

                        Select::make('fuel_type')
                            ->label('燃料類型')
                            ->options([
                                Vehicle::FUEL_PETROL => '汽油',
                                Vehicle::FUEL_DIESEL => '柴油',
                                Vehicle::FUEL_ELECTRIC => '電動',
                                Vehicle::FUEL_HYBRID => '油電混合',
                            ])
                            ->nullable()
                            ->placeholder('選擇燃料類型'),

                        Select::make('transmission')
                            ->label('變速箱')
                            ->options([
                                Vehicle::TRANSMISSION_AUTOMATIC => '自排',
                                Vehicle::TRANSMISSION_MANUAL => '手排',
                            ])
                            ->nullable()
                            ->placeholder('選擇變速箱類型'),
                    ]),

                Section::make('維修管理')
                    ->description('維修中車輛的相關時間與備註資訊，僅在狀態為"維修中"時需要填寫')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 6,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        DateTimePicker::make('maintenance_start_at')
                            ->label('維修開始時間')
                            ->nullable()
                            ->placeholder('選擇維修開始時間'),

                        DateTimePicker::make('maintenance_expected_end_at')
                            ->label('預計維修完成時間')
                            ->nullable()
                            ->placeholder('選擇預計完成時間'),

                        Textarea::make('maintenance_notes')
                            ->label('維修備註')
                            ->rows(3)
                            ->nullable()
                            ->placeholder('輸入維修相關備註')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ]),
            ]);
    }
}
