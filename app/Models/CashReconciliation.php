<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CashReconciliation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'date',
        'system_total',
        'counted_total',
        'difference',
        'attempts',
        'reconciled_by',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'system_total'  => 'double',
        'counted_total' => 'double',
        'difference'    => 'double',
        'attempts'      => 'integer',
        'date'          => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (CashReconciliation $rec) {
            if (empty($rec->sync_id)) {
                $rec->sync_id = (string) Str::uuid();
            }
        });
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function scopeForSubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }
}
