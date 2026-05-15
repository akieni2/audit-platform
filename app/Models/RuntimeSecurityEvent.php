<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuntimeSecurityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_context_id',
        'user_id',
        'mission_id',
        'severity',
        'event_type',
        'threat_level',
        'blocked',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
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

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
