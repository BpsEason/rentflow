<?php

namespace App\Http\Requests\Api\VehiclePricings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehiclePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekday_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'weekend_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'holiday_price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
