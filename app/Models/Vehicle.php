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

    protected $fillable = [
        'tenant_id',
        'name',
        'plate_number',
        'status',
        'seats',
        'description',
    ];

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
