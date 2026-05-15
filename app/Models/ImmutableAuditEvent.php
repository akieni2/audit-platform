<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmutableAuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_context_id',
        'user_id',
        'event_type',
        'module',
        'resource_type',
        'resource_id',
        'action_signature',
        'integrity_hash',
        'previous_hash',
        'description',
        'payload',
        'ip',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function tenantContext(): BelongsTo
    {
        return $this->belongsTo(TenantContext::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
