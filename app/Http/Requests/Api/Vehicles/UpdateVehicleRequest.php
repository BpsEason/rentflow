<?php

namespace App\Http\Requests\Api\Vehicles;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'plate_number' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'string', 'in:AVAILABLE,MAINTENANCE,INACTIVE'],
            'seats' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'manufacture_year' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:' . date('Y')],
            'mileage' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'fuel_type' => ['sometimes', 'nullable', 'string', 'in:PETROL,DIESEL,ELECTRIC,HYBRID'],
            'transmission' => ['sometimes', 'nullable', 'string', 'in:AUTOMATIC,MANUAL'],
            'maintenance_start_at' => ['sometimes', 'nullable', 'date'],
            'maintenance_expected_end_at' => ['sometimes', 'nullable', 'date', 'after:maintenance_start_at'],
            'maintenance_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
