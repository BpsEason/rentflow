<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use App\Models\Order;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    use HasDefaultFormLayout;

    protected static ?string $model = Order::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|UnitEnum|null $navigationGroup = '租車管理';
    protected static ?string $navigationLabel = '訂單管理';
    protected static ?string $modelLabel = '訂單';
    protected static ?string $pluralModelLabel = '訂單列表';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return self::applyDefaultLayout(OrderForm::configure($schema));
    }

    public static function table(Table $table): Table
    {
        return $table->columns(OrdersTable::getColumns())
            ->filters(OrdersTable::getFilters())
            ->actions(OrdersTable::getActions())
            ->bulkActions(OrdersTable::getBulkActions())
            ->modifyQueryUsing(function ($query) {
                return $query->with(['reservation.customer', 'reservation.vehicle']);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function createNewOrderFromReservation(int $reservationId): Order
    {
        $reservation = Reservation::findOrFail($reservationId);
        return Order::createFromReservation($reservation);
    }
}
