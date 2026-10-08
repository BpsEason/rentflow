<?php

namespace App\Application\Reservations\UseCases;

use App\Domain\Reservations\DTO\CreateReservationData;
use App\Domain\Reservations\Repositories\ReservationRepositoryInterface;
use App\Domain\Reservations\Events\ReservationCreated;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Event;

class CreateReservation
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * 執行創建預約的邏輯
     */
    public function execute(CreateReservationData $data)
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (!$tenantId) {
            throw new \RuntimeException('無法確定當前租戶', 403);
        }

        // 使用 Domain Model 既有的 createReservation 方法 (包含車輛狀態, 最少租用時間, 時間衝突與金額計算)
        $reservation = \App\Models\Reservation::createReservation([
            'tenant_id' => $tenantId,
            'vehicle_id' => $data->vehicleId,
            'customer_id' => $data->customerId,
            'start_at' => $data->startDate,
            'end_at' => $data->endDate,
            'notes' => $data->notes,
            'status' => \App\Models\Reservation::STATUS_PENDING,
        ]);

        // 觸發預約創建事件
        Event::dispatch(new ReservationCreated($reservation));

        return $reservation;
    }
}
