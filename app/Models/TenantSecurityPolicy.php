<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSecurityPolicy extends Model
{
    protected $fillable = [
        'tenant_context_id',
        'mfa_required',
        'strict_session_binding',
        'max_session_minutes',
        'signed_actions_required',
        'api_access_enabled',
        'allowed_modules',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'mfa_required' => 'boolean',
            'strict_session_binding' => 'boolean',
            'signed_actions_required' => 'boolean',
            'api_access_enabled' => 'boolean',
            'allowed_modules' => 'array',
            'metadata' => 'array',
        ];
    }

    public function tenantContext(): BelongsTo
    {
        return $this->belongsTo(TenantContext::class);
    }
}
