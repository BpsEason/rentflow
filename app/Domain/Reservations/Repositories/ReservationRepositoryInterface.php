<?php

namespace App\Domain\Reservations\Repositories;

use App\Models\Reservation;
use App\Application\Reservations\DTOs\CreateReservationData;
use Carbon\Carbon;

interface ReservationRepositoryInterface
{
    /**
     * 尋找指定租戶的預約
     */
    public function findForTenant(int $reservationId, int $tenantId): ?Reservation;

    /**
     * 檢查車輛在指定時間是否有衝突（相容性方法）
     */
    public function hasTimeConflict(int $vehicleId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): bool;

    /**
     * 檢查車輛在指定時間是否可用（新方法名稱）
     */
    public function isVehicleAvailable(int $vehicleId, Carbon $startDate, Carbon $endDate): bool;

    /**
     * 創建預約
     */
    public function create(array $data): Reservation;

    /**
     * 更新預約狀態
     */
    public function updateStatus(Reservation $reservation, string $status, ?string $reason = null): Reservation;

    /**
     * 取得當前租戶的所有預約
     */
    public function getAllForTenant(int $tenantId);
}
