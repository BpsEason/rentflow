<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Reservation",
 *     type="object",
 *     title="Reservation",
 *     description="預約資源",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="tenant_id", type="integer", example=1),
 *     @OA\Property(property="vehicle_id", type="integer", example=5),
 *     @OA\Property(property="customer_id", type="integer", example=12),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", example="2024-01-05T10:00:00Z"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'vehicle_id' => $this->vehicle_id,
            'customer_id' => $this->customer_id,
            'start_at' => $this->start_at?->toISOString() ?? $this->start_date?->toISOString(),
            'end_at' => $this->end_at?->toISOString() ?? $this->end_date?->toISOString(),
            'start_date' => $this->start_at?->toISOString() ?? $this->start_date?->toISOString(),
            'end_date' => $this->end_at?->toISOString() ?? $this->end_date?->toISOString(),
            'status' => $this->status,
            'amount' => (float)$this->amount,
            'rental_days' => $this->rental_days,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
