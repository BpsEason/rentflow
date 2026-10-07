<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'tenant_id',
        'reservation_id',
        'order_number',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->reservation->customer();
    }

    public function vehicle(): BelongsTo
    {
        return $this->reservation->vehicle();
    }

    public static function generateOrderNumber(int $tenantId): string
    {
        $prefix = 'ORD-' . str_pad($tenantId, 3, '0', STR_PAD_LEFT) . '-';
        $lastOrder = self::where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastOrder) {
            return $prefix . str_pad(1, 6, '0', STR_PAD_LEFT);
        }

        $lastNumber = (int) substr($lastOrder->order_number, strlen($prefix));
        return $prefix . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    public static function createFromReservation(Reservation $reservation): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($reservation) {
            // 驗證租戶一致性 - 使用者必須屬於預約所屬的租戶（僅在有認證使用者時檢查）
            if (auth()->check()) {
                $userTenants = auth()->user()->tenants->pluck('id')->toArray();
                if (!in_array($reservation->tenant_id, $userTenants)) {
                    throw new \RuntimeException('Cannot create order from a reservation belonging to another tenant.');
                }
            }

            // 驗證訂單是否已存在
            if (self::where('reservation_id', $reservation->id)->exists()) {
                throw new \RuntimeException('Order already exists for this reservation.');
            }

            // 驗證預約狀態必須是已確認才能建立訂單
            if (!in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PICKED_UP])) {
                throw new \RuntimeException('Cannot create order from an unconfirmed reservation.');
            }

            return self::create([
                'tenant_id' => $reservation->tenant_id,
                'reservation_id' => $reservation->id,
                'order_number' => self::generateOrderNumber($reservation->tenant_id),
                'status' => self::STATUS_PENDING,
                'total_amount' => $reservation->amount,
            ]);
        });
    }

    public function scopeTenant(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }
}
