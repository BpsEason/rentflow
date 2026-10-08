<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class RecentReservationsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenant = filament()->getTenant();
        $tenantId = $tenant->id;

        return $table
            ->query(
                Reservation::with(['customer', 'vehicle'])
                    ->where('tenant_id', $tenantId)
                    ->latest()
                    ->limit(10)
            )
            ->columns(ReservationsTable::getColumns())
            ->actions([])
            ->bulkActions([]);
    }
}
