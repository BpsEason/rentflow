<?php

namespace App\Filament\Resources\VehiclePricings\Schemas;

use App\Domain\Rental\Models\Vehicle;
use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class VehiclePricingForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var Tenant|null $tenant */
        $tenant = filament()->getTenant();

        return $schema
            ->components([
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
                    ->preload(),

                TextInput::make('weekday_price')
                    ->label('平日單價')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),

                TextInput::make('weekend_price')
                    ->label('週末單價')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),

                TextInput::make('holiday_price')
                    ->label('國定假日單價')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),
            ]);
    }
}
