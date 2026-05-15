<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAccessEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_context_id',
        'user_id',
        'access_type',
        'resource_type',
        'resource_id',
        'outcome',
        'metadata',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'accessed_at' => 'datetime',
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
