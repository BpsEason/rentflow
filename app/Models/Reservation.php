<?php

namespace App\Models;

use App\Services\PricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Reservation extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'PENDING';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_PICKED_UP = 'PICKED_UP';
    const STATUS_RETURNED = 'RETURNED';
    const STATUS_CANCELLED = 'CANCELLED';

    const BLOCKING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PICKED_UP,
    ];

    const NON_BLOCKING_STATUSES = [
        self::STATUS_RETURNED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'vehicle_id',
        'start_at',
        'end_at',
        'status',
        'amount',
        'rental_days',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Order::class);
    }

    public static function hasTimeConflict(int $vehicleId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): bool
    {
        $query = self::where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    public static function calculateRentalDays(Carbon $startAt, Carbon $endAt): int
    {
        return app(PricingService::class)->calculateRentalDays($startAt, $endAt);
    }

    public static function calculateAmount(Vehicle $vehicle, Carbon $startAt, Carbon $endAt): array
    {
        return app(PricingService::class)->calculateTotalAmount($vehicle, $startAt, $endAt);
    }

    public static function validateMinimumRentalTime(Carbon $startAt, Carbon $endAt): bool
    {
        $diffInHours = $startAt->diffInHours($endAt);
        return $diffInHours >= 4;
    }

    public static function createReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
            $vehicle = Vehicle::where('id', $data['vehicle_id'])
                ->where('tenant_id', $data['tenant_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($vehicle->status !== 'AVAILABLE') {
                throw new \Exception('車輛目前無法租用，車輛ID: ' . $vehicle->id . '，狀態: ' . $vehicle->status);
            }

            $customer = Customer::where('id', $data['customer_id'])
                ->where('tenant_id', $data['tenant_id'])
                ->firstOrFail();

            $startAt = Carbon::parse($data['start_at']);
            $endAt = Carbon::parse($data['end_at']);

            if ($endAt->lte($startAt)) {
                throw new \Exception('結束時間必須晚於開始時間');
            }

            if (!self::validateMinimumRentalTime($startAt, $endAt)) {
                throw new \Exception('最少租用時間為4小時');
            }

            if (self::hasTimeConflict($vehicle->id, $startAt, $endAt)) {
                throw new \Exception('指定時間區間內車輛已被預約');
            }

            $pricingData = self::calculateAmount($vehicle, $startAt, $endAt);

            return self::create([
                'tenant_id' => $data['tenant_id'],
                'customer_id' => $data['customer_id'],
                'vehicle_id' => $data['vehicle_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $data['status'] ?? self::STATUS_PENDING,
                'amount' => $pricingData['amount'],
                'rental_days' => $pricingData['rental_days'],
            ]);
        });
    }

    public function updateReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
            $vehicleId = $data['vehicle_id'] ?? $this->vehicle_id;
            $vehicle = Vehicle::where('id', $vehicleId)
                ->where('tenant_id', $this->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($vehicle->status !== 'AVAILABLE') {
                throw new \Exception('車輛目前無法租用');
            }

            if (isset($data['customer_id'])) {
                Customer::where('id', $data['customer_id'])
                    ->where('tenant_id', $this->tenant_id)
                    ->firstOrFail();
            }

            $startAt = isset($data['start_at']) ? Carbon::parse($data['start_at']) : $this->start_at;
            $endAt = isset($data['end_at']) ? Carbon::parse($data['end_at']) : $this->end_at;

            if ($endAt->lte($startAt)) {
                throw new \Exception('結束時間必須晚於開始時間');
            }

            if (!self::validateMinimumRentalTime($startAt, $endAt)) {
                throw new \Exception('最少租用時間為4小時');
            }

            if (self::hasTimeConflict($vehicle->id, $startAt, $endAt, $this->id)) {
                throw new \Exception('指定時間區間內車輛已被預約');
            }

            $pricingData = self::calculateAmount($vehicle, $startAt, $endAt);

            $this->update([
                'customer_id' => $data['customer_id'] ?? $this->customer_id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $data['status'] ?? $this->status,
                'amount' => $pricingData['amount'],
                'rental_days' => $pricingData['rental_days'],
            ]);

            return $this;
        });
    }
}
