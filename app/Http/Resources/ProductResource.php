<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'subscriber_id'     => $this->subscriber_id,
            'name'              => $this->name,
            'price'             => $this->price,
            'cost_price'        => $this->cost_price,
            'category'          => $this->category,
            'description'       => $this->description,
            'stock'             => $this->stock,
            'stock_alert_level' => $this->stock_alert_level,
            'image_url'         => $this->resolveImageAsBase64($this->image_url),
            'sync_id'           => $this->sync_id,
            'juice_ml_per_unit' => $this->juice_ml_per_unit,
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
            'variants'          => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }

    private function resolveImageAsBase64(?string $imageUrl): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        // Already base64 (not a path)
        if (! str_starts_with($imageUrl, '/storage/')) {
            return $imageUrl;
        }

        // Convert stored file path → base64
        $relativePath = str_replace('/storage/', '', $imageUrl);
        if (Storage::disk('public')->exists($relativePath)) {
            return base64_encode(Storage::disk('public')->get($relativePath));
        }

        return null;
    }
}
