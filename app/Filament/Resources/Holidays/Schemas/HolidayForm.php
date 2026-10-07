<?php

namespace App\Filament\Resources\Holidays\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HolidayForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('國定假日設定')
                    ->description('新增國定假日日期，系統將自動套用假日價格計算')
                    ->icon('heroicon-o-calendar')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        DatePicker::make('date')
                            ->label('假日日期')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('選擇假日日期'),

                        TextInput::make('name')
                            ->label('假日名稱')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('輸入假日名稱，例如：春節、元旦'),
                    ]),
            ]);
    }
}
