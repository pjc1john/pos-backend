<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaleItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'sync_status',
        'sale_id',
        'sale_sync_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'sale_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'double',
        'total_price' => 'double',
    ];

    protected static function booted(): void
    {
        static::creating(function (SaleItem $item) {
            if (empty($item->sync_id)) {
                $item->sync_id = (string) Str::uuid();
            }
        });
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
