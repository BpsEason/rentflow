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
        'current_branch_id',
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

    /**
     * 車輛目前所在地的據點
     */
    public function currentBranch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    /**
     * 查詢指定時間點車輛的預計位置據點ID
     * 用於判斷未來預約時車輛是否能在指定據點
     */
    public function getExpectedBranchIdAt(\Carbon\Carbon $dateTime): ?int
    {
        // 找到在指定時間之前最後一個完成的預約
        $lastReservation = $this->reservations()
            ->whereIn('status', [Reservation::STATUS_RETURNED])
            ->where('end_at', '<=', $dateTime)
            ->orderByDesc('end_at')
            ->first();

        if ($lastReservation) {
            // 如果有最後一個完成的預約，車輛會在該預約的還車據點
            return $lastReservation->return_branch_id;
        }

        // 如果沒有任何歷史預約，使用目前的據點
        return $this->current_branch_id;
    }

    /**
     * 檢查車輛在指定時間是否能到位於指定據點
     */
    public function canBeAtBranchAt(int $branchId, \Carbon\Carbon $dateTime): bool
    {
        $expectedBranchId = $this->getExpectedBranchIdAt($dateTime);
        return $expectedBranchId === $branchId;
    }
}
