<?php

namespace App\Domain\Rental\Models;

use App\Models\Tenant;
use App\Domain\Rental\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'plate_number',
        'status',
        'seats',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(VehiclePricing::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
