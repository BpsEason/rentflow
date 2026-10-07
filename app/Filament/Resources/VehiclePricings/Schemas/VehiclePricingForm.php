<?php

namespace App\Filament\Resources\VehiclePricings\Schemas;

use App\Domain\Rental\Models\Vehicle;
use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class VehiclePricingForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var Tenant|null $tenant */
        $tenant = filament()->getTenant();

        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('車輛關聯')
                    ->description('選擇要設定價格的車輛，每台車輛僅需設定一次價格')
                    ->icon('heroicon-o-truck')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        Select::make('vehicle_id')
                            ->label('選擇車輛')
                            ->options(function () use ($user, $tenant) {
                                $query = Vehicle::query();

                                // Super Admin can see all vehicles
                                if (!$user->hasRole('Super Admin') && $tenant) {
                                    $query->where('tenant_id', $tenant->id);
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('請選擇車輛'),
                    ]),

                Section::make('價格設定')
                    ->description('依照不同日期類型設定租賃單價，金額單位為新台幣')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        TextInput::make('weekday_price')
                            ->label('平日單價 (周一至周五)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->placeholder('輸入平日租賃價格'),

                        TextInput::make('weekend_price')
                            ->label('週末單價 (周六至周日)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->placeholder('輸入週末租賃價格'),

                        TextInput::make('holiday_price')
                            ->label('國定假日單價')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->placeholder('輸入國定假日租賃價格')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ]),
            ]);
    }
}
