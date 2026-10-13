<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    const STATUS_AVAILABLE = 'AVAILABLE';
    const STATUS_MAINTENANCE = 'MAINTENANCE';
    const STATUS_INACTIVE = 'INACTIVE';

    const FUEL_PETROL = 'PETROL';
    const FUEL_DIESEL = 'DIESEL';
    const FUEL_ELECTRIC = 'ELECTRIC';
    const FUEL_HYBRID = 'HYBRID';

    const TRANSMISSION_AUTOMATIC = 'AUTOMATIC';
    const TRANSMISSION_MANUAL = 'MANUAL';

    protected $fillable = [
        'tenant_id',
        'name',
        'plate_number',
        'status',
        'seats',
        'description',
        'image',
        'manufacture_year',
        'mileage',
        'fuel_type',
        'transmission',
        'maintenance_start_at',
        'maintenance_expected_end_at',
        'maintenance_notes',
    ];

    protected function casts(): array
    {
        return [
            'manufacture_year' => 'integer',
            'mileage' => 'integer',
            'maintenance_start_at' => 'datetime',
            'maintenance_expected_end_at' => 'datetime',
        ];
    }

    /**
     * Get the full URL for the vehicle image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }

        return null;
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pricing(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VehiclePricing::class);
    }

    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
