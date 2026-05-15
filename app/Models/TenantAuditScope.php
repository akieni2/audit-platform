<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAuditScope extends Model
{
    protected $fillable = [
        'tenant_context_id',
        'module',
        'immutable_trail_enabled',
        'retention_days',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'immutable_trail_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function tenantContext(): BelongsTo
    {
        return $this->belongsTo(TenantContext::class);
    }
}
