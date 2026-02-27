<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'sync_status',
        'subscriber_id',
        'receipt_number',
        'user_id',
        'branch_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'payment_method',
        'amount_received',
        'change_amount',
        'created_at',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'sync_status' => 'string',
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'total_amount' => 'double',
        'discount_amount' => 'double',
        'final_amount' => 'double',
        'amount_received' => 'double',
        'change_amount' => 'double',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if (empty($sale->sync_id)) {
                $sale->sync_id = (string) Str::uuid();
            }
        });
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function scopeForSubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }
}
