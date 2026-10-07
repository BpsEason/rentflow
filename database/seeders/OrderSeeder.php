<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 從現有的已確認預約建立訂單
        $confirmedReservations = Reservation::where('status', Reservation::STATUS_CONFIRMED)
            ->orWhere('status', Reservation::STATUS_PICKED_UP)
            ->get();

        foreach ($confirmedReservations as $reservation) {
            // 檢查是否已經有訂單
            if (!Order::where('reservation_id', $reservation->id)->exists()) {
                Order::createFromReservation($reservation);
            }
        }
    }
}
