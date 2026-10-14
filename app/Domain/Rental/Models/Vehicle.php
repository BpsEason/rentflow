<?php

namespace App\Domain\Rental\Models;

use App\Models\Tenant;
use App\Domain\Rental\Enums\VehicleStatus;
use App\Domain\Rental\Enums\VehicleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    // Fuel type constants
    public const FUEL_PETROL = 'PETROL';
    public const FUEL_DIESEL = 'DIESEL';
    public const FUEL_ELECTRIC = 'ELECTRIC';
    public const FUEL_HYBRID = 'HYBRID';

    // Transmission type constants
    public const TRANSMISSION_AUTOMATIC = 'AUTOMATIC';
    public const TRANSMISSION_MANUAL = 'MANUAL';

    protected $fillable = [
        'tenant_id',
        'name',
        'plate_number',
        'type',
        'status',
        'seats',
        'description',
        'image',
        'manufacture_year',
        'mileage',
        'fuel_type',
        'transmission',
        'engine_cc',
        'helmet_count',
        'maintenance_start_at',
        'maintenance_expected_end_at',
        'maintenance_notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'status' => VehicleStatus::class,
            'manufacture_year' => 'integer',
            'mileage' => 'integer',
            'engine_cc' => 'integer',
            'helmet_count' => 'integer',
            'maintenance_start_at' => 'datetime',
            'maintenance_expected_end_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pricing(): HasOne
    {
        return $this->hasOne(\App\Domain\Rental\Models\VehiclePricing::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
