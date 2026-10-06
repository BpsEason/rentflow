<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Schemas\Components\Form;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('name')
                        ->label('名稱')
                        ->required()
                        ->unique(ignoreRecord: true),
                    TextInput::make('guard_name')
                        ->label('守衛名稱')
                        ->required()
                        ->default('web'),
                ]),
            ]);
    }
}
