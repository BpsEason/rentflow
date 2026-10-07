<?php

namespace App\Domain\Rental\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'available';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';
}
