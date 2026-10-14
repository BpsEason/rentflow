<?php

namespace App\Http\Requests\Api\Vehicles;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'in:CAR,MOTORCYCLE'],
            'status' => ['nullable', 'string', 'in:AVAILABLE,MAINTENANCE,INACTIVE'],
            'seats' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'manufacture_year' => ['nullable', 'integer', 'min:2000', 'max:' . date('Y')],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'in:PETROL,DIESEL,ELECTRIC,HYBRID'],
            'transmission' => ['nullable', 'string', 'in:AUTOMATIC,MANUAL'],
            'engine_cc' => ['nullable', 'integer', 'min:50', 'max:1000'],
            'helmet_count' => ['nullable', 'integer', 'min:0', 'max:5'],
            'maintenance_start_at' => ['nullable', 'date'],
            'maintenance_expected_end_at' => ['nullable', 'date', 'after:maintenance_start_at'],
            'maintenance_notes' => ['nullable', 'string'],
        ];
    }
}
