<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SyncConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'key',
        'value',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'branch_id'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (SyncConfig $model) {
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
