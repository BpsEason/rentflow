<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 訪客都可以提交預約，不需要登入
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // 車輛資訊
            'vehicle_id' => 'required|integer|exists:vehicles,id',

            // 據點資訊
            'pickup_branch_id' => 'required|integer|exists:branches,id',
            'return_branch_id' => 'required|integer|exists:branches,id',

            // 時間資訊
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|string',
            'return_date' => 'required|date|after:pickup_date',
            'return_time' => 'required|string',

            // 客戶基本資料
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'customer_id_number' => 'required|string|max:20',

            // 駕駛人資訊
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',

            // 選填資訊
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ];
    }
}
