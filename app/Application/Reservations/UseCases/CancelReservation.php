<?php

namespace App\Application\Reservations\UseCases;

use App\Domain\Reservations\Repositories\ReservationRepositoryInterface;
use App\Domain\Reservations\Events\ReservationCancelled;
use App\Models\Reservation;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Event;

class CancelReservation
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * 執行取消預約的邏輯
     */
    public function execute(int $reservationId, ?string $reason = null)
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (!$tenantId) {
            throw new \RuntimeException('無法確定當前租戶', 403);
        }

        // 取得當前租戶的預約
        $reservation = $this->reservationRepository->findForTenant($reservationId, $tenantId);

        if (!$reservation) {
            throw new \RuntimeException('預約不存在', 404);
        }

        // 檢查預約狀態是否可以取消
        if (!in_array($reservation->status, [\App\Models\Reservation::STATUS_PENDING, \App\Models\Reservation::STATUS_CONFIRMED])) {
            throw new \RuntimeException('此預約無法被取消', 409);
        }

        // 更新預約狀態
        $reservation = $this->reservationRepository->updateStatus($reservation, Reservation::STATUS_CANCELLED, $reason);

        // 觸發預約取消事件
        Event::dispatch(new ReservationCancelled($reservation));

        return $reservation;
    }
}
