<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VariantInventoryLink extends Model
{
    use SoftDeletes;

    protected $table = 'variant_inventory_links';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'variant_id',
        'inventory_item_id',
        'quantity_per_unit',
    ];

    protected $casts = [
        'quantity_per_unit' => 'float',
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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
