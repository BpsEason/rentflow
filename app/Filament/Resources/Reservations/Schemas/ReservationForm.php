<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ReservationForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = filament()->getTenant();

        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('預約資訊')
                    ->description('填寫預約的基本資訊')
                    ->icon('heroicon-o-calendar')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('客戶')
                            ->options(function () use ($user, $tenant) {
                                $query = Customer::query();

                                if (!$user->hasRole('Super Admin') && $tenant) {
                                    $query->where('tenant_id', $tenant->id);
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->preload()
                            ->placeholder('選擇客戶'),

                        Forms\Components\Select::make('vehicle_id')
                            ->label('車輛')
                            ->options(function () use ($user, $tenant) {
                                $query = Vehicle::query();

                                if (!$user->hasRole('Super Admin') && $tenant) {
                                    $query->where('tenant_id', $tenant->id);
                                }

                                return $query->where('status', 'AVAILABLE')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->preload()
                            ->placeholder('選擇車輛'),

                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('開始時間')
                            ->required()
                            ->placeholder('選擇開始時間'),

                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('結束時間')
                            ->required()
                            ->placeholder('選擇結束時間'),
                    ]),

                Section::make('預約狀態與費用')
                    ->description('預約狀態與系統計算的費用資訊')
                    ->icon('heroicon-o-information-circle')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('狀態')
                            ->options([
                                Reservation::STATUS_PENDING => '待確認',
                                Reservation::STATUS_CONFIRMED => '已確認',
                                Reservation::STATUS_PICKED_UP => '已取車',
                                Reservation::STATUS_RETURNED => '已歸還',
                                Reservation::STATUS_CANCELLED => '已取消',
                            ])
                            ->required()
                            ->placeholder('選擇狀態'),

                        Forms\Components\TextInput::make('rental_days')
                            ->label('租用天數')
                            ->disabled()
                            ->placeholder('系統自動計算'),

                        Forms\Components\TextInput::make('amount')
                            ->label('總金額')
                            ->disabled()
                            ->prefix('$')
                            ->placeholder('系統自動計算'),
                    ]),
            ]);
    }
}
