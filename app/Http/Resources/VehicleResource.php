<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Vehicle",
 *     type="object",
 *     title="Vehicle",
 *     description="車輛資源",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="tenant_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Toyota Altis"),
 *     @OA\Property(property="plate_number", type="string", example="ABC-1234"),
 *     @OA\Property(property="status", type="string", example="AVAILABLE"),
 *     @OA\Property(property="seats", type="integer", example=5),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 確保 image_url 有一個 fallback
        $coverImage = $this->image_url ?: 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1200';

        // 處理 images 陣列
        $images = $this->images ?? [];
        if (empty($images)) {
            // 如果沒有 images 陣列，則使用封面圖作為唯一圖片
            $images = [$coverImage];
        } else {
            // 如果有 images 陣列，確保封面圖是第一張
            if (($key = array_search($coverImage, $images)) !== false) {
                unset($images[$key]);
            }
            array_unshift($images, $coverImage);
        }

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'plate_number' => $this->plate_number,
            'status' => $this->status,
            'seats' => $this->seats,
            'description' => $this->description,
            'image_url' => $coverImage, // 回傳處理過的封面圖
            'images' => array_values($images), // 重新索引陣列
            'pricing' => new VehiclePricingResource($this->whenLoaded('pricing')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
