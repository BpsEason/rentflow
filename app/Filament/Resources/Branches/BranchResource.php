<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Filament\Resources\Branches\Schemas\BranchForm;
use App\Filament\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = '租戶管理';

    protected static ?string $navigationLabel = '據點管理';

    protected static ?string $modelLabel = '據點';

    protected static ?string $pluralModelLabel = '據點';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Super Admin can see all branches
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();

        // For tenant users, only show branches belonging to the current tenant
        if ($tenant) {
            return $query->where('tenant_id', $tenant->id);
        }

        // If no tenant is selected and not Super Admin, return empty query
        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
