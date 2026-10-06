<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Schemas\Components\Form;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
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
                    TextInput::make('slug')
                        ->label('識別碼')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),
            ]);
    }
}
