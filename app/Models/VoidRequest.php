<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VoidRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sync_id',
        'subscriber_id',
        'branch_id',
        'sale_sync_id',
        'receipt_number',
        'requested_by',
        'requested_at',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'branch_id'     => 'integer',
        'requested_at'  => 'datetime',
        'reviewed_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (VoidRequest $voidRequest) {
            if (empty($voidRequest->sync_id)) {
                $voidRequest->sync_id = (string) Str::uuid();
            }
        });
    }
}
