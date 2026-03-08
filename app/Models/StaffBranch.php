<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StaffBranch extends Model
{

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'user_id',
        'branch_id',
        'assigned_date',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'user_id'       => 'integer',
        'branch_id'     => 'integer',
        'assigned_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (StaffBranch $model) {
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
