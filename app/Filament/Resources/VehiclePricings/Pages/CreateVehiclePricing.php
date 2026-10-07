<?php

namespace App\Filament\Resources\VehiclePricings\Pages;

use App\Filament\Resources\VehiclePricings\VehiclePricingResource;
use App\Models\Tenant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVehiclePricing extends CreateRecord
{
    protected static string $resource = VehiclePricingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var Tenant|null $tenant */
        $tenant = filament()->getTenant();

        // 若非超級管理員，強制使用當前租戶ID
        if (!$user->hasRole('Super Admin') && $tenant) {
            $data['tenant_id'] = $tenant->id;
        }

        return $data;
    }
}
