<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Reservation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $reservation = Reservation::findOrFail($data['reservation_id']);

        return Order::createFromReservation($reservation);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 如果已經選擇了預約，預先填入金額
        if (isset($data['reservation_id'])) {
            $reservation = Reservation::find($data['reservation_id']);
            if ($reservation) {
                $data['total_amount'] = $reservation->amount;
                $data['order_number'] = Order::generateOrderNumber($reservation->tenant_id);
            }
        }

        return $data;
    }
}
