<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            // 據點資訊
            // 據點資訊 - 必須存在、屬於當前租戶且已啟用
            'pickup_branch_id' => ['required', 'integer', 'exists:branches,id', function ($attribute, $value, $fail) {
                $tenant = app(\App\Support\TenantContext::class)->getCurrentTenant();
                if (!$tenant) {
                    $fail('無法解析租戶資訊');
                    return;
                }
                $branch = \App\Models\Branch::where('id', $value)
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->first();
                if (!$branch) {
                    $fail('取車據點無效，請選擇有效的據點');
                }
            }],
            'return_branch_id' => ['required', 'integer', 'exists:branches,id', function ($attribute, $value, $fail) {
                $tenant = app(\App\Support\TenantContext::class)->getCurrentTenant();
                if (!$tenant) {
                    $fail('無法解析租戶資訊');
                    return;
                }
                $branch = \App\Models\Branch::where('id', $value)
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->first();
                if (!$branch) {
                    $fail('還車據點無效，請選擇有效的據點');
                }
            }],
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|string',
            'return_date' => 'required|date|after:pickup_date',
            'return_time' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            // 客戶資料欄位 - 僅當用戶在當前租戶尚未建立Customer資料時需要
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_id_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ];
    }
}
