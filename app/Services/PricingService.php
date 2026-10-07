<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehiclePricing;
use App\Models\Holiday;
use Carbon\Carbon;

class PricingService
{
    /**
     * 計算租用天數
     * 每不足24小時仍計算為一天
     */
    public function calculateRentalDays(Carbon $startAt, Carbon $endAt): int
    {
        $diffInMinutes = $startAt->diffInMinutes($endAt);
        return (int)ceil($diffInMinutes / (24 * 60));
    }

    /**
     * 計算總金額
     * 依照日期類型逐日計算：Holiday > Weekend > Weekday
     */
    public function calculateTotalAmount(Vehicle $vehicle, Carbon $startAt, Carbon $endAt): array
    {
        $rentalDays = $this->calculateRentalDays($startAt, $endAt);
        $pricing = $vehicle->pricing;

        if (!$pricing) {
            throw new \Exception('此車輛尚未設定價格');
        }

        // 取得該租戶的所有假日
        $holidays = Holiday::where('tenant_id', $vehicle->tenant_id)
            ->get()
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date))
            ->toArray();

        $totalAmount = 0;
        $current = $startAt->copy();

        for ($i = 0; $i < $rentalDays; $i++) {
            $isHoliday = collect($holidays)->contains(fn($holiday) => $holiday->isSameDay($current));
            $isWeekend = in_array($current->dayOfWeek, [0, 6]); // 0 = Sunday, 6 = Saturday

            if ($isHoliday) {
                $totalAmount += $pricing->holiday_price;
            } elseif ($isWeekend) {
                $totalAmount += $pricing->weekend_price;
            } else {
                $totalAmount += $pricing->weekday_price;
            }

            $current->addDay();
        }

        return [
            'rental_days' => $rentalDays,
            'amount' => $totalAmount,
        ];
    }
}
