<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 如果不是Super Admin，確保不能修改tenant_id
        if (!$user->hasRole('Super Admin')) {
            // 強制保留原始的tenant_id，防止修改
            $data['tenant_id'] = $record->tenant_id;
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
