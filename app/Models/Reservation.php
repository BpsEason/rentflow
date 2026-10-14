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
        'pickup_branch_id',
        'return_branch_id',
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

    /**
     * 取車據點
     */
    public function pickupBranch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class, 'pickup_branch_id');
    }

    /**
     * 還車據點
     */
    public function returnBranch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class, 'return_branch_id');
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

            if ($vehicle->status !== \App\Domain\Rental\Enums\VehicleStatus::AVAILABLE) {
                throw new \Exception('車輛目前無法租用', 400);
            }

            $startAt = Carbon::parse($data['start_at']);
            $endAt = Carbon::parse($data['end_at']);

            // 檢查車輛是否處於維修時段內，如果預約時間與維修時間重疊，則不可租用
            if ($vehicle->maintenance_start_at && $vehicle->maintenance_expected_end_at) {
                $maintenanceStart = Carbon::parse($vehicle->maintenance_start_at);
                $maintenanceEnd = Carbon::parse($vehicle->maintenance_expected_end_at);

                if ($startAt->lessThan($maintenanceEnd) && $endAt->greaterThan($maintenanceStart)) {
                    throw new \Exception('車輛目前處於維修時段，無法租用', 400);
                }
            }

            $customer = Customer::where('id', $data['customer_id'])
                ->where('tenant_id', $data['tenant_id'])
                ->firstOrFail();

            if ($endAt->lte($startAt)) {
                throw new \Exception('結束時間必須晚於開始時間', 422);
            }

            if (!self::validateMinimumRentalTime($startAt, $endAt)) {
                throw new \Exception('最少租用時間為4小時');
            }

            if (self::hasTimeConflict($vehicle->id, $startAt, $endAt)) {
                throw new \Exception('指定時間區間內車輛已被預約', 409);
            }

            // 驗證取車與還車據點（必須提供）
            if (!isset($data['pickup_branch_id'], $data['return_branch_id'])) {
                throw new \Exception('取車與還車據點必須同時指定', 422);
            }

            // 驗證兩個據點都屬於當前租戶且為啟用狀態
            $validBranches = \App\Models\Branch::where('tenant_id', $data['tenant_id'])
                ->where('is_active', true)
                ->whereIn('id', [$data['pickup_branch_id'], $data['return_branch_id']])
                ->count();

            if ($validBranches !== 2) {
                throw new \Exception('取車或還車據點無效，請確認據點屬於您的租戶且已啟用', 400);
            }

            // 檢查車輛是否能在取車時間到位於取車據點
            if (!$vehicle->canBeAtBranchAt($data['pickup_branch_id'], $startAt)) {
                $expectedBranchId = $vehicle->getExpectedBranchIdAt($startAt);
                $expectedBranch = $expectedBranchId ? \App\Models\Branch::find($expectedBranchId)?->name : '未知據點';
                throw new \Exception("車輛在取車時間預計位於【{$expectedBranch}】，無法在指定據點取車", 400);
            }

            $pricingData = self::calculateAmount($vehicle, $startAt, $endAt);

            $reservationData = [
                'tenant_id' => $data['tenant_id'],
                'customer_id' => $data['customer_id'],
                'vehicle_id' => $data['vehicle_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $data['status'] ?? self::STATUS_PENDING,
                'amount' => $pricingData['amount'],
                'rental_days' => $pricingData['rental_days'],
            ];

            // 如果有提供取還車據點，一併儲存
            if (isset($data['pickup_branch_id'], $data['return_branch_id'])) {
                $reservationData['pickup_branch_id'] = $data['pickup_branch_id'];
                $reservationData['return_branch_id'] = $data['return_branch_id'];
            }

            return self::create($reservationData);
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

            if ($vehicle->status !== \App\Domain\Rental\Enums\VehicleStatus::AVAILABLE) {
                throw new \Exception('車輛目前無法租用');
            }

            if (isset($data['customer_id'])) {
                Customer::where('id', $data['customer_id'])
                    ->where('tenant_id', $this->tenant_id)
                    ->firstOrFail();
            }

            $startAt = isset($data['start_at']) ? Carbon::parse($data['start_at']) : $this->start_at;
            $endAt = isset($data['end_at']) ? Carbon::parse($data['end_at']) : $this->end_at;

            // 檢查車輛是否處於維修時段內，如果預約時間與維修時間重疊，則不可租用
            if ($vehicle->maintenance_start_at && $vehicle->maintenance_expected_end_at) {
                $maintenanceStart = Carbon::parse($vehicle->maintenance_start_at);
                $maintenanceEnd = Carbon::parse($vehicle->maintenance_expected_end_at);

                if ($startAt->lessThan($maintenanceEnd) && $endAt->greaterThan($maintenanceStart)) {
                    throw new \Exception('車輛目前處於維修時段，無法租用', 400);
                }
            }

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

    /**
     * 確認預約
     */
    public function confirm(): void
    {
        if (!in_array($this->status, [self::STATUS_PENDING])) {
            throw new \RuntimeException('只有待確認狀態的預約可以被確認');
        }

        $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    /**
     * 標記為已取車
     */
    public function markAsPickedUp(): void
    {
        if (!in_array($this->status, [self::STATUS_CONFIRMED])) {
            throw new \RuntimeException('只有已確認狀態的預約可以標記為已取車');
        }

        $this->update(['status' => self::STATUS_PICKED_UP]);
    }

    /**
     * 標記為已歸還，並更新車輛的目前據點
     */
    public function markAsReturned(): void
    {
        if (!in_array($this->status, [self::STATUS_PICKED_UP])) {
            throw new \RuntimeException('只有已取車狀態的預約可以標記為已歸還');
        }

        DB::transaction(function () {
            // 更新預約狀態
            $this->update(['status' => self::STATUS_RETURNED]);

            // 更新車輛的目前所在地為此預約的還車據點
            if ($this->return_branch_id) {
                $this->vehicle()->update(['current_branch_id' => $this->return_branch_id]);
            }
        });
    }

    /**
     * 取消預約
     */
    public function cancel(): void
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED])) {
            throw new \RuntimeException('只有待確認或已確認狀態的預約可以被取消');
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * 標記為已取車
     */
    public function pickUp(): void
    {
        if (!in_array($this->status, [self::STATUS_CONFIRMED])) {
            throw new \RuntimeException('只有已確認狀態的預約可以辦理取車');
        }

        $this->update(['status' => self::STATUS_PICKED_UP]);

        // 確保訂單已創建
        if (!$this->order()->exists()) {
            \App\Models\Order::createFromReservation($this);
        }
    }

    /**
     * 標記為已歸還
     */
    public function return(): void
    {
        if (!in_array($this->status, [self::STATUS_PICKED_UP])) {
            throw new \RuntimeException('只有已取車狀態的預約可以辦理歸還');
        }

        $this->update(['status' => self::STATUS_RETURNED]);
    }
}
