<?php

namespace App\Application\Reservations\UseCases;

use App\Domain\Reservations\Repositories\ReservationRepositoryInterface;
use App\Domain\Reservations\Events\ReservationConfirmed;
use App\Models\Reservation;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Event;

class ConfirmReservation
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * 執行確認預約的邏輯
     */
    public function execute(int $reservationId)
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

        // 檢查預約狀態是否可以確認
        if ($reservation->status !== \App\Models\Reservation::STATUS_PENDING) {
            throw new \RuntimeException('只有待確認的預約可以被確認', 409);
        }

        // 更新預約狀態
        $reservation = $this->reservationRepository->updateStatus($reservation, Reservation::STATUS_CONFIRMED);

        // 觸發預約確認事件
        Event::dispatch(new ReservationConfirmed($reservation));

        return $reservation;
    }
}
