<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalaryRecord extends Model
{
    use SoftDeletes;

    protected $table = 'salary_records';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'user_id',
        'amount',
        'pay_period_start',
        'pay_period_end',
        'deductions',
        'bonuses',
        'net_amount',
        'paid_date',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'subscriber_id'   => 'integer',
        'branch_id'       => 'integer',
        'user_id'         => 'integer',
        'amount'          => 'double',
        'deductions'      => 'double',
        'bonuses'         => 'double',
        'net_amount'      => 'double',
        'pay_period_start' => 'date',
        'pay_period_end'   => 'date',
        'paid_date'       => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalaryRecord $record) {
            if (empty($record->sync_id)) {
                $record->sync_id = (string) Str::uuid();
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
