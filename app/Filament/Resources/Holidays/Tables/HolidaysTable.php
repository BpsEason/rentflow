<?php

namespace App\Filament\Resources\Holidays\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class HolidaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('date')->label('日期')->date()->sortable()->searchable(),
                TextColumn::make('name')->label('名稱')->searchable()->sortable(),
                TextColumn::make('created_at')->label('建立時間')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('編輯'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('批次刪除'),
                ]),
            ]);
    }
}
