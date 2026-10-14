<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 如果不是Super Admin，強制使用目前的租戶
        if (!$user->hasRole('Super Admin')) {
            $tenant = filament()->getTenant();
            $data['tenant_id'] = $tenant->id;
        }

        return static::getModel()::create($data);
    }
}
