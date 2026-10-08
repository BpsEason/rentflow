<?php

namespace App\Application\Reservations\DTOs;

readonly class CancelReservationData
{
    public function __construct(
        public int $reservationId,
    ) {}
}
