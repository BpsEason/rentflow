<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Domain\Rental\Enums\VehicleStatus;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('車輛名稱')
                    ->required()
                    ->maxLength(255),

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
                    }),

                Select::make('status')
                    ->label('車輛狀態')
                    ->options(VehicleStatus::class)
                    ->required(),

                TextInput::make('seats')
                    ->label('座位數')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50),

                Textarea::make('description')
                    ->label('車輛描述')
                    ->rows(3)
                    ->nullable(),
            ]);
    }
}
