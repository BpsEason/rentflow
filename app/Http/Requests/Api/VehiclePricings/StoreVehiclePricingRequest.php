<?php

namespace App\Http\Requests\Api\VehiclePricings;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehiclePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer'],
            'weekday_price' => ['required', 'numeric', 'min:0'],
            'weekend_price' => ['required', 'numeric', 'min:0'],
            'holiday_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
