<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'phone',
        'business_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'business_hours' => 'json',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 所屬租戶
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 此據點目前的車輛
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'current_branch_id');
    }

    /**
     * 從此據點取車的預約
     */
    public function pickupReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'pickup_branch_id');
    }

    /**
     * 在此據點還車的預約
     */
    public function returnReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'return_branch_id');
    }
}
