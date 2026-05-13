<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessEvent extends Model
{
    protected $fillable = [
        'event_name',
        'aggregate_type',
        'aggregate_id',
        'mission_id',
        'actor_user_id',
        'correlation_id',
        'causation_id',
        'idempotency_key',
        'status',
        'queue',
        'payload',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }
}
