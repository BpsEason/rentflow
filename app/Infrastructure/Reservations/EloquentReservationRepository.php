<?php

namespace App\Infrastructure\Reservations;

use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Domain\Reservations\Repositories\ReservationRepositoryInterface;
use App\Application\Reservations\DTOs\CreateReservationData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentReservationRepository implements ReservationRepositoryInterface
{
    public function findForTenant(int $reservationId, int $tenantId): ?Reservation
    {
        return Reservation::where('id', $reservationId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function hasTimeConflict(int $vehicleId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): bool
    {
        $query = Reservation::where('vehicle_id', $vehicleId)
            ->whereIn('status', Reservation::BLOCKING_STATUSES)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }



    public function updateStatus(Reservation $reservation, string $status, ?string $reason = null): Reservation
    {
        $updateData = ['status' => $status];

        if ($reason) {
            $updateData['cancellation_reason'] = $reason;
        }

        $reservation->update($updateData);

        return $reservation;
    }

    public function isVehicleAvailable(int $vehicleId, Carbon $startDate, Carbon $endDate): bool
    {
        return !$this->hasTimeConflict($vehicleId, $startDate, $endDate);
    }

    public function create(array $data): Reservation
    {
        return Reservation::create($data);
    }

    public function getAllForTenant(int $tenantId)
    {
        return Reservation::where('tenant_id', $tenantId)->get();
    }
}
