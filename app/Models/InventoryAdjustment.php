<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryAdjustment extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_adjustments';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'inventory_item_id',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reason',
        'adjusted_by',
        'notes',
    ];

    protected $casts = [
        'quantity_before' => 'float',
        'quantity_change' => 'float',
        'quantity_after'  => 'float',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->sync_id)) {
                $model->sync_id = (string) Str::uuid();
            }
        });
    }

    public function scopeForSubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
