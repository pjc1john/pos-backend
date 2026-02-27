<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dtr extends Model
{
    use SoftDeletes;

    protected $table = 'dtr';

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'username',
        'time_in',
        'time_out',
        'date',
        'total_hours',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'total_hours'   => 'double',
        'time_in'       => 'datetime',
        'time_out'      => 'datetime',
        'date'          => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Dtr $dtr) {
            if (empty($dtr->sync_id)) {
                $dtr->sync_id = (string) Str::uuid();
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
