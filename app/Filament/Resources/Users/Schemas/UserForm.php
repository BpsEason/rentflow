<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Tenant;
use Filament\Schemas\Components\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('name')
                        ->label('名稱')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('電子郵件')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('password')
                        ->label('密碼')
                        ->password()
                        ->dehydrated(fn($state) => filled($state) ? Hash::make($state) : null)
                        ->required(fn(string $context) => $context === 'create')
                        ->minLength(8),
                    Select::make('roles')
                        ->label('角色')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->options(Role::all()->pluck('name', 'id')),
                    Select::make('tenants')
                        ->label('租戶')
                        ->relationship('tenants', 'name')
                        ->multiple()
                        ->preload()
                        ->options(Tenant::all()->pluck('name', 'id')),
                ]),
            ]);
    }
}
