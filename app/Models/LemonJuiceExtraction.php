<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LemonJuiceExtraction extends Model
{
    use SoftDeletes;

    protected $table = 'lemon_juice_extractions';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'date',
        'amount_ml',
        'lemons_for_extraction',
        'lemons_for_slices',
        'inventory_item_id',
        'notes',
    ];

    protected $casts = [
        'date'                  => 'date',
        'amount_ml'             => 'double',
        'lemons_for_extraction' => 'double',
        'lemons_for_slices'     => 'double',
        'inventory_item_id'     => 'integer',
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
