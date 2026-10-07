<?php

namespace App\Filament\Resources\VehiclePricings\Pages;

use App\Filament\Resources\VehiclePricings\VehiclePricingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehiclePricings extends ListRecords
{
    protected static string $resource = VehiclePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
