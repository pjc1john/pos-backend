<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'branch_id'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (BusinessSetting $setting) {
            if (empty($setting->sync_id)) {
                $setting->sync_id = (string) Str::uuid();
            }
        });
    }

    public function scopeForSubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }
}
