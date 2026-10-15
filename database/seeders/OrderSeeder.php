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
        // 從現有的已確認、已取車或已歸還預約建立訂單
        $confirmedReservations = Reservation::whereIn('status', [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PICKED_UP,
            Reservation::STATUS_RETURNED
        ])
            ->get();

        foreach ($confirmedReservations as $reservation) {
            // 檢查是否已經有訂單
            if (!Order::where('reservation_id', $reservation->id)->exists()) {
                Order::createFromReservation($reservation);
            }
        }
    }
}
