<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\CustomerUserLinkService;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();

        if ($tenant) {
            $data['tenant_id'] = $tenant->id;
            app(CustomerUserLinkService::class)->assertCanLink(
                isset($data['user_id']) ? (int) $data['user_id'] : null,
                $tenant->id,
            );
        }

        return $data;
    }
}
