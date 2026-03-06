<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductRecipeItem extends Model
{
    use SoftDeletes;

    protected $table = 'product_recipe_items';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'product_id',
        'variant_id',
        'inventory_item_id',
        'quantity',
        'waste_factor',
        'is_optional',
        'notes',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'waste_factor' => 'float',
        'is_optional'  => 'boolean',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
