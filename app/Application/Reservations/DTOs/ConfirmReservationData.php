<?php

namespace App\Application\Reservations\DTOs;

readonly class ConfirmReservationData
{
    public function __construct(
        public int $reservationId,
    ) {}
}
