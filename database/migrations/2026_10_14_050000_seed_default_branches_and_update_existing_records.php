<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\Reservation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 為每個沒有據點的租戶建立預設據點
        Tenant::chunk(100, function ($tenants) {
            foreach ($tenants as $tenant) {
                // 檢查租戶是否已有任何據點
                $hasBranch = Branch::where('tenant_id', $tenant->id)->exists();

                if (!$hasBranch) {
                    // 建立預設據點
                    Branch::create([
                        'tenant_id' => $tenant->id,
                        'name' => '預設據點',
                        'address' => $tenant->address ?? '暫無地址',
                        'phone' => $tenant->phone ?? '0000000000',
                        'is_active' => true,
                    ]);
                }
            }
        });

        // 將目前沒有current_branch_id的車輛，關聯到所屬租戶的第一個啟用據點
        Vehicle::whereNull('current_branch_id')
            ->chunk(100, function ($vehicles) {
                foreach ($vehicles as $vehicle) {
                    $defaultBranch = Branch::where('tenant_id', $vehicle->tenant_id)
                        ->where('is_active', true)
                        ->first();

                    if ($defaultBranch) {
                        $vehicle->update(['current_branch_id' => $defaultBranch->id]);
                    }
                }
            });

        // 處理既有預約：如果取車或還車據點為空，先檢查關聯車輛的current_branch_id
        // 若車輛已有據點，則使用車輛的據點作為取還車據點（避免覆寫已有資料）
        Reservation::where(function ($query) {
            $query->whereNull('pickup_branch_id')
                ->orWhereNull('return_branch_id');
        })
            ->chunk(100, function ($reservations) {
                foreach ($reservations as $reservation) {
                    // 只有當兩個欄位都為空時才回填，避免覆寫單一已填寫的欄位
                    if (is_null($reservation->pickup_branch_id) && is_null($reservation->return_branch_id)) {
                        $vehicle = $reservation->vehicle;
                        if ($vehicle && $vehicle->current_branch_id) {
                            $reservation->update([
                                'pickup_branch_id' => $vehicle->current_branch_id,
                                'return_branch_id' => $vehicle->current_branch_id,
                            ]);
                        } else {
                            // 如果車輛也沒有據點，使用租戶的預設據點
                            $defaultBranch = Branch::where('tenant_id', $reservation->tenant_id)
                                ->where('is_active', true)
                                ->first();

                            if ($defaultBranch) {
                                $reservation->update([
                                    'pickup_branch_id' => $defaultBranch->id,
                                    'return_branch_id' => $defaultBranch->id,
                                ]);
                            }
                        }
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 此遷移僅做資料回填，不刪除任何資料
        // 若要rollback，請先手動處理相關資料，避免遺失
    }
};
