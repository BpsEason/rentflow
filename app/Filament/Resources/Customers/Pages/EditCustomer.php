<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\EditRecord;
use App\Services\CustomerUserLinkService;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(CustomerUserLinkService::class)->assertCanLink(
            isset($data['user_id']) ? (int) $data['user_id'] : null,
            $this->record->tenant_id,
            $this->record->getKey(),
        );

        return $data;
    }
}
