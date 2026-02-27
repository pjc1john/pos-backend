<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductInventoryLink extends Model
{
    use SoftDeletes;

    protected $table = 'product_inventory_links';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'product_id',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
