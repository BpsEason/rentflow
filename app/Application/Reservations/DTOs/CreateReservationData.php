<?php

namespace App\Application\Reservations\DTOs;

use Carbon\Carbon;

readonly class CreateReservationData
{
    public function __construct(
        public int $customerId,
        public int $vehicleId,
        public Carbon $startAt,
        public Carbon $endAt,
    ) {}
}
