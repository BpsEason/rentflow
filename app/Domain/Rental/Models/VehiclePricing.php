<?php

namespace App\Domain\Rental\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePricing extends Model
{
    protected $table = 'vehicle_pricing';

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'weekday_price',
        'weekend_price',
        'holiday_price',
    ];

    protected $casts = [
        'weekday_price' => 'decimal:2',
        'weekend_price' => 'decimal:2',
        'holiday_price' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
