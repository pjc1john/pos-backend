<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Discount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscriber_id',
        'branch_id',
        'sync_id',
        'sync_status',
        'name',
        'type',
        'value',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value'       => 'decimal:2',
            'is_active'   => 'boolean',
            'subscriber_id' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->sync_id)) {
                $model->sync_id = (string) Str::uuid();
            }
        });
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
