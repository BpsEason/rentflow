<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="VehiclePricing",
 *     type="object",
 *     title="VehiclePricing",
 *     description="車輛價格設定資源",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="tenant_id", type="integer", example=1),
 *     @OA\Property(property="vehicle_id", type="integer", example=1),
 *     @OA\Property(property="weekday_price", type="number", format="float", example=1500.00),
 *     @OA\Property(property="weekend_price", type="number", format="float", example=2000.00),
 *     @OA\Property(property="holiday_price", type="number", format="float", example=2500.00),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class VehiclePricingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'vehicle_id' => $this->vehicle_id,
            'weekday_price' => (float)$this->weekday_price,
            'weekend_price' => (float)$this->weekend_price,
            'holiday_price' => (float)$this->holiday_price,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
