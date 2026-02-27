<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'sync_status',
        'subscriber_id',
        'name',
        'address',
        'phone',
        'status',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (empty($branch->sync_id)) {
                $branch->sync_id = (string) Str::uuid();
            }
        });
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
