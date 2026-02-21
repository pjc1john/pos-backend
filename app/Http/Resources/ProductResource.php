<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscriber_id' => $this->subscriber_id,
            'name' => $this->name,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'category' => $this->category,
            'description' => $this->description,
            'stock' => $this->stock,
            'stock_alert_level' => $this->stock_alert_level,
            'image_url' => $this->image_url,
            'sync_id' => $this->sync_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
