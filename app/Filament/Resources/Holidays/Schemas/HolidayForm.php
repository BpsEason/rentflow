<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('日期')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('名稱')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
