<?php

namespace App\Domain\Rental\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case MAINTENANCE = 'MAINTENANCE';
    case INACTIVE = 'INACTIVE';
}
