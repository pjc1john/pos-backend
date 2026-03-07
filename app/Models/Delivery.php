<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Delivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'branch_name',
        'delivery_date',
        'status',
        'notes',
        'driver_name',
        'vehicle_number',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'branch_id'     => 'integer',
        'delivery_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Delivery $delivery) {
            if (empty($delivery->sync_id)) {
                $delivery->sync_id = (string) Str::uuid();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function scopeForSubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }
}
