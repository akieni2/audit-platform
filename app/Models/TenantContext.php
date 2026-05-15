<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TenantContext extends Model
{
    protected $fillable = [
        'department_id',
        'tenant_key',
        'isolation_mode',
        'cache_prefix',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function securityPolicy(): HasOne
    {
        return $this->hasOne(TenantSecurityPolicy::class);
    }

    public function auditScopes(): HasMany
    {
        return $this->hasMany(TenantAuditScope::class);
    }
}
