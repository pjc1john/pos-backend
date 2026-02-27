<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sync_id' => $this->sync_id,
            'subscriber_id' => $this->subscriber_id,
            'receipt_number' => $this->receipt_number,
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'final_amount' => $this->final_amount,
            'payment_method' => $this->payment_method,
            'amount_received' => $this->amount_received,
            'change_amount' => $this->change_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
