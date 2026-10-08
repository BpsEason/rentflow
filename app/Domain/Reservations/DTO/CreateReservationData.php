<?php

namespace App\Domain\Reservations\DTO;

use Carbon\Carbon;

class CreateReservationData
{
    public function __construct(
        public readonly int $vehicleId,
        public readonly int $customerId,
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly ?string $notes = null
    ) {}

    /**
     * 從請求數據建立DTO
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vehicleId: $data['vehicle_id'],
            customerId: $data['customer_id'],
            startDate: Carbon::parse($data['start_at']),
            endDate: Carbon::parse($data['end_at']),
            notes: $data['notes'] ?? null
        );
    }
}
