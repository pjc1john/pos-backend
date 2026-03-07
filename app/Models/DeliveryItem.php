<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DeliveryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'delivery_id',
        'inventory_item_id',
        'item_name',
        'quantity',
        'unit',
        'batch_number',
    ];

    protected $casts = [
        'subscriber_id'     => 'integer',
        'branch_id'         => 'integer',
        'delivery_id'       => 'integer',
        'inventory_item_id' => 'integer',
        'quantity'          => 'double',
    ];

    protected static function booted(): void
    {
        static::creating(function (DeliveryItem $item) {
            if (empty($item->sync_id)) {
                $item->sync_id = (string) Str::uuid();
            }
        });
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function scopeForSubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }
}
