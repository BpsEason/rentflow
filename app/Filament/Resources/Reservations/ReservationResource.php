<?php

namespace App\Filament\Resources\Reservations;

use App\Filament\Resources\Reservations\Pages;
use App\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReservationResource extends Resource
{
    use HasDefaultFormLayout;

    protected static ?string $model = Reservation::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;
    protected static string|UnitEnum|null $navigationGroup = '租車管理';
    protected static ?string $navigationLabel = '預約管理';
    protected static ?string $modelLabel = '預約';
    protected static ?string $pluralModelLabel = '預約列表';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table->columns(ReservationsTable::getColumns())
            ->filters(ReservationsTable::getFilters())
            ->actions(ReservationsTable::getActions())
            ->bulkActions(ReservationsTable::getBulkActions());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
        ];
    }
}
