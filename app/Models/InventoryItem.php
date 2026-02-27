<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'name',
        'category',
        'quantity',
        'unit',
        'min_stock_level',
        'expiration_date',
        'batch_number',
        'supplier',
        'cost_per_unit',
        'notes',
    ];

    protected $casts = [
        'quantity'        => 'float',
        'min_stock_level' => 'float',
        'cost_per_unit'   => 'float',
        'expiration_date' => 'date',
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
}
