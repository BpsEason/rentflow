<?php

namespace App\Filament\Resources\VehiclePricings\Pages;

use App\Filament\Resources\VehiclePricings\VehiclePricingResource;
use App\Models\Tenant;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVehiclePricing extends EditRecord
{
    protected static string $resource = VehiclePricingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var Tenant|null $tenant */
        $currentTenant = filament()->getTenant();

        // 非超級管理員只能編輯自己租戶的定價
        if (!$user->hasRole('Super Admin') && $currentTenant && $data['tenant_id'] != $currentTenant->id) {
            abort(403);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
