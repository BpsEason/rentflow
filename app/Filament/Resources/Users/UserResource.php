<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = '平台管理';

    protected static ?string $navigationLabel = '使用者管理';

    protected static ?string $modelLabel = '使用者';

    protected static ?string $pluralModelLabel = '使用者';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = auth()->user();

        // Super Admin can see all users
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        /** @var Tenant $tenant */
        $tenant = filament()->getTenant();

        // For tenant users, only show users belonging to the current tenant
        if ($tenant) {
            return $query->whereHas('tenants', function (Builder $query) use ($tenant) {
                $query->where('tenants.id', $tenant->id);
            });
        }

        // If no tenant is selected and not Super Admin, return empty query
        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
