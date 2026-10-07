<?php

namespace App\Filament\Resources\VehiclePricings;

use App\Filament\Resources\VehiclePricings\Pages\CreateVehiclePricing;
use App\Filament\Resources\VehiclePricings\Pages\EditVehiclePricing;
use App\Filament\Resources\VehiclePricings\Pages\ListVehiclePricings;
use App\Filament\Resources\VehiclePricings\Schemas\VehiclePricingForm;
use App\Filament\Resources\VehiclePricings\Tables\VehiclePricingsTable;
use App\Domain\Rental\Models\VehiclePricing;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VehiclePricingResource extends Resource
{
    protected static ?string $model = VehiclePricing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = '租戶管理';

    protected static ?string $navigationLabel = '車輛定價';

    protected static ?string $modelLabel = '車輛定價';

    protected static ?string $pluralModelLabel = '車輛定價';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Super Admin can see all vehicle pricings
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();

        // For tenant users, only show pricings belonging to the current tenant
        if ($tenant) {
            return $query->where('tenant_id', $tenant->id);
        }

        // If no tenant is selected and not Super Admin, return empty query
        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return VehiclePricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehiclePricingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehiclePricings::route('/'),
            'create' => CreateVehiclePricing::route('/create'),
            'edit' => EditVehiclePricing::route('/{record}/edit'),
        ];
    }
}
