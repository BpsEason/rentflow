<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use App\Models\Order;
use App\Models\Reservation;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class OrderForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('訂單基本資訊')
                    ->description('訂單的基本識別資訊，訂單編號由系統自動產生')
                    ->icon('heroicon-o-document-text')
                    ->columnSpan(['default' => 1, 'lg' => 7])
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('訂單編號')
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('reservation_id')
                            ->label('關聯預約')
                            ->options(fn() => Reservation::where('tenant_id', filament()->getTenant()->id)
                                ->with(['vehicle', 'customer'])
                                ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PICKED_UP])
                                ->whereDoesntHave('order')
                                ->get()
                                ->mapWithKeys(fn($reservation) => [
                                    $reservation->id => "#{$reservation->id} - {$reservation->vehicle->name} - {$reservation->customer->name}"
                                ]))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                    ]),

                Section::make('訂單金額與狀態')
                    ->description('訂單的金額資訊與目前處理狀態')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('總金額')
                            ->prefix('TWD')
                            ->numeric()
                            ->disabled()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('狀態')
                            ->options([
                                Order::STATUS_PENDING => '待處理',
                                Order::STATUS_CONFIRMED => '已確認',
                                Order::STATUS_CANCELLED => '已取消',
                                Order::STATUS_COMPLETED => '已完成',
                            ])
                            ->required(),
                    ]),
            ]);
    }
}
